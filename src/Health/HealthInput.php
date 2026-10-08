<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Health;

final readonly class HealthInput
{
    public function __construct(
        public int $calls,
        public int $failed,
        public ?float $p50 = null,
        public ?float $p95 = null,
        public ?float $ceilingSeconds = null,
        public ?float $baselineErrorPct = null,
        public bool $knownBroken = false,
    ) {}

    public function errorPct(): float
    {
        return $this->calls > 0 ? round($this->failed / $this->calls * 100, 1) : 0.0;
    }
}
