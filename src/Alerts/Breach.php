<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

final readonly class Breach
{
    /** @param  array<string, mixed>  $context */
    public function __construct(
        public string $key,
        public float $value,
        public Severity $severity,
        public array $context = [],
    ) {}
}
