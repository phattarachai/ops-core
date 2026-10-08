<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

use DateTimeImmutable;
use Phattarachai\OpsCore\Alerts\Contracts\IncidentStore;

final readonly class AlertEngine
{
    public function __construct(
        private RuleEvaluator $evaluator,
        private IncidentStore $incidents,
        private IncidentLifecycle $lifecycle = new IncidentLifecycle,
    ) {}

    /**
     * @param  iterable<RuleSpec>  $rules
     * @return list<IncidentNotice>
     */
    public function run(iterable $rules, DateTimeImmutable $now, ?string $environment = null): array
    {
        $notices = [];

        foreach ($rules as $rule) {
            $notices = [...$notices, ...$this->runRule($rule, $now, $environment)];
        }

        return $notices;
    }

    /** @return list<IncidentNotice> */
    public function runRule(RuleSpec $rule, DateTimeImmutable $now, ?string $environment = null): array
    {
        $open = $this->incidents->openFor($rule->id);

        $result = $rule->active && $rule->appliesTo($environment)
            ? $this->lifecycle->reconcile($rule, $this->evaluator->evaluate($rule, $now), $open, $now, silent: $rule->isMuted($now))
            : $this->lifecycle->resolveAll($open, $now);

        return $this->persist($result);
    }

    /** @return list<IncidentNotice> */
    private function persist(LifecycleResult $result): array
    {
        $saved = [];

        foreach ($result->changed as $incident) {
            $saved[$this->identity($incident)] = $this->incidents->save($incident);
        }

        return array_map(
            fn (IncidentNotice $notice): IncidentNotice => new IncidentNotice($notice->kind, $saved[$this->identity($notice->incident)] ?? $notice->incident, $notice->rule),
            $result->notices,
        );
    }

    private function identity(Incident $incident): string
    {
        return "{$incident->ruleId}|{$incident->key}";
    }
}
