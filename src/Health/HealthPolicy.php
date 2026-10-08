<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Health;

/**
 * Thresholds, in error-rate percentage points, for baseline-relative health.
 */
final readonly class HealthPolicy
{
    public function __construct(
        public float $incidentDeviation = 10.0,
        public float $degradedDeviation = 3.0,
        public float $pinnedIncidentDeviation = 5.0,
        public float $chronicErrorFloor = 20.0,
        public float $slowMedianRatio = 0.5,
    ) {}
}
