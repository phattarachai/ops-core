<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

use DateTimeImmutable;
use Phattarachai\OpsCore\Alerts\Contracts\SignalReader;

final readonly class RuleEvaluator
{
    public function __construct(private SignalReader $signals) {}

    /** @return list<Breach> */
    public function evaluate(RuleSpec $rule, DateTimeImmutable $now): array
    {
        $breaches = match ($rule->type) {
            RuleType::Threshold => $this->threshold($rule, $now),
            RuleType::Rate => $this->rate($rule, $now),
            RuleType::Absence => $this->absence($rule, $now),
            RuleType::State => $this->state($rule),
        };

        return array_values(array_filter($breaches, static fn (Breach $breach): bool => $rule->allowsKey($breach->key)));
    }

    /** @return list<Breach> */
    private function threshold(RuleSpec $rule, DateTimeImmutable $now): array
    {
        $counts = $rule->distinctField === null
            ? $this->signals->countsByKey($rule->signal, $rule->windowStart($now), $now)
            : $this->signals->distinctCountsByKey($rule->signal, $rule->distinctField, $rule->windowStart($now), $now);

        $breaches = [];

        foreach ($counts as $key => $count) {
            if ($count >= $rule->threshold) {
                $breaches[] = new Breach((string) $key, (float) $count, $rule->severity, ['count' => $count, 'window_seconds' => $rule->windowSeconds]);
            }
        }

        return $breaches;
    }

    /** @return list<Breach> */
    private function rate(RuleSpec $rule, DateTimeImmutable $now): array
    {
        $errors = $this->signals->countsByKey($rule->signal, $rule->windowStart($now), $now);
        $totals = $this->signals->countsByKey($rule->denominatorSignal ?? $rule->signal, $rule->windowStart($now), $now);
        $breaches = [];

        foreach ($totals as $key => $total) {
            $failed = $errors[$key] ?? 0;
            $percent = $total > 0 ? round($failed / $total * 100, 2) : 0.0;

            if ($total >= max(1, $rule->minSamples) && $percent >= $rule->threshold) {
                $breaches[] = new Breach((string) $key, $percent, $rule->severity, ['failed' => $failed, 'total' => $total, 'window_seconds' => $rule->windowSeconds]);
            }
        }

        return $breaches;
    }

    /** @return list<Breach> */
    private function absence(RuleSpec $rule, DateTimeImmutable $now): array
    {
        $breaches = [];

        foreach ($this->signals->lastSeenByKey($rule->signal) as $key => $lastSeen) {
            $silentFor = $now->getTimestamp() - $lastSeen->getTimestamp();

            if ($silentFor >= $rule->threshold) {
                $breaches[] = new Breach((string) $key, (float) $silentFor, $rule->severity, ['last_seen_at' => $lastSeen->format(DATE_ATOM)]);
            }
        }

        return $breaches;
    }

    /** @return list<Breach> */
    private function state(RuleSpec $rule): array
    {
        $breaches = [];

        foreach ($this->signals->statesByKey($rule->signal) as $key => $reading) {
            if ($reading->level->reaches($rule->minLevel)) {
                $breaches[] = new Breach((string) $key, (float) $reading->level->rank(), $reading->level->severity(), [
                    'level' => $reading->level->value,
                    'value' => $reading->value,
                    'message' => $reading->message,
                    'at' => $reading->at->format(DATE_ATOM),
                ]);
            }
        }

        return $breaches;
    }
}
