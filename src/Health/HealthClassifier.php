<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Health;

/**
 * Baseline-relative health: a constant failure rate is "known broken", a change against the
 * subject's own trailing baseline is an incident, and an absolute floor stops a chronically
 * failing subject from ever reading healthy.
 */
final readonly class HealthClassifier
{
    public function __construct(private HealthPolicy $policy = new HealthPolicy) {}

    public function classify(HealthInput $input): HealthVerdict
    {
        $errorPct = $input->errorPct();
        $deviation = $this->deviation($input, $errorPct);
        $pinned = $this->pinnedAtCeiling($input);

        return new HealthVerdict($this->health($input, $errorPct, $deviation, $pinned), $errorPct, $deviation, $pinned);
    }

    public function forFailureRate(int $runs, int $failed): Health
    {
        return match (true) {
            $runs === 0 => Health::NoData,
            $failed === 0 => Health::Ok,
            $failed / $runs * 100 >= $this->policy->chronicErrorFloor => Health::Incident,
            default => Health::Degraded,
        };
    }

    private function health(HealthInput $input, float $errorPct, ?float $deviation, bool $pinned): Health
    {
        return match (true) {
            $input->calls === 0 => Health::NoData,
            $pinned && $deviation !== null && $deviation > $this->policy->pinnedIncidentDeviation => Health::Incident,
            $input->knownBroken => Health::KnownBroken,
            $deviation !== null && $deviation > $this->policy->incidentDeviation => Health::Incident,
            $errorPct >= $this->policy->chronicErrorFloor => Health::KnownBroken,
            $deviation !== null && $deviation > $this->policy->degradedDeviation => Health::Degraded,
            $this->slowMedian($input) => Health::Degraded,
            default => Health::Ok,
        };
    }

    private function deviation(HealthInput $input, float $errorPct): ?float
    {
        return $input->calls === 0 || $input->baselineErrorPct === null
            ? null
            : round($errorPct - $input->baselineErrorPct, 1);
    }

    private function pinnedAtCeiling(HealthInput $input): bool
    {
        return $input->calls > 0
            && $input->p95 !== null
            && $input->ceilingSeconds !== null
            && $input->p95 >= $input->ceilingSeconds;
    }

    private function slowMedian(HealthInput $input): bool
    {
        return $input->p50 !== null
            && $input->ceilingSeconds !== null
            && $input->p50 > $input->ceilingSeconds * $this->policy->slowMedianRatio;
    }
}
