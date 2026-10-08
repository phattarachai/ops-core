<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Health;

final readonly class HealthVerdict
{
    public function __construct(
        public Health $health,
        public float $errorPct,
        public ?float $deviation,
        public bool $pinnedAtCeiling,
    ) {}
}
