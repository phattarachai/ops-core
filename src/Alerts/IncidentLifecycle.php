<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

use DateTimeImmutable;

/**
 * Open → re-notify after the cooldown (or at once on escalation) → resolve once the rule has
 * stayed clear for `resolveAfterSeconds`. A silent pass moves state but sends nothing. A rule
 * with `remindWarnings` off only reminds about critical incidents.
 */
final class IncidentLifecycle
{
    /** @var list<Incident> */
    private array $changed = [];

    /** @var list<IncidentNotice> */
    private array $notices = [];

    /**
     * @param  list<Breach>  $breaches
     * @param  list<Incident>  $open
     */
    public function reconcile(RuleSpec $rule, array $breaches, array $open, DateTimeImmutable $now, bool $silent = false): LifecycleResult
    {
        $this->changed = [];
        $this->notices = [];
        $openByKey = $this->byKey($open);

        foreach ($breaches as $breach) {
            $this->breach($rule, $breach, $openByKey[$breach->key] ?? null, $now, $silent);
            unset($openByKey[$breach->key]);
        }

        foreach ($openByKey as $incident) {
            $this->clear($rule, $incident, $now, $silent);
        }

        return new LifecycleResult($this->changed, $this->notices);
    }

    /** @param  list<Incident>  $open */
    public function resolveAll(array $open, DateTimeImmutable $now): LifecycleResult
    {
        return new LifecycleResult(array_map(static fn (Incident $incident): Incident => $incident->resolved($now), $open));
    }

    private function breach(RuleSpec $rule, Breach $breach, ?Incident $incident, DateTimeImmutable $now, bool $silent): void
    {
        if (! $incident instanceof Incident) {
            $this->record($rule, Incident::open($rule->id, $breach, $now), NoticeKind::Opened, $now, $silent);

            return;
        }

        $updated = $incident->breached($breach, $now);
        $kind = $this->repeatNotice($rule, $incident, $updated, $now);

        $this->record($rule, $updated, $kind, $now, $silent);
    }

    private function clear(RuleSpec $rule, Incident $incident, DateTimeImmutable $now, bool $silent): void
    {
        if ($incident->secondsSinceBreached($now) < $rule->resolveAfterSeconds) {
            return;
        }

        $kind = $rule->notifyOnResolve && $incident->notifications > 0 ? NoticeKind::Resolved : null;

        $this->record($rule, $incident->resolved($now), $kind, $now, $silent);
    }

    private function repeatNotice(RuleSpec $rule, Incident $before, Incident $after, DateTimeImmutable $now): ?NoticeKind
    {
        $sinceNotified = $before->secondsSinceNotified($now);

        return match (true) {
            $after->severity->outranks($before->severity) => NoticeKind::Escalated,
            $sinceNotified === null => NoticeKind::Opened,
            ! $this->remindable($rule, $after) => null,
            $rule->cooldownSeconds > 0 && $sinceNotified >= $rule->cooldownSeconds => NoticeKind::Reminder,
            default => null,
        };
    }

    private function remindable(RuleSpec $rule, Incident $incident): bool
    {
        return $rule->remindWarnings || $incident->severity === Severity::Critical;
    }

    private function record(RuleSpec $rule, Incident $incident, ?NoticeKind $kind, DateTimeImmutable $now, bool $silent): void
    {
        if ($kind === null || $silent) {
            $this->changed[] = $incident;

            return;
        }

        $notified = $kind === NoticeKind::Resolved ? $incident : $incident->notified($now);

        $this->changed[] = $notified;
        $this->notices[] = new IncidentNotice($kind, $notified, $rule);
    }

    /**
     * @param  list<Incident>  $incidents
     * @return array<string, Incident>
     */
    private function byKey(array $incidents): array
    {
        $byKey = [];

        foreach ($incidents as $incident) {
            $byKey[$incident->key] = $incident;
        }

        return $byKey;
    }
}
