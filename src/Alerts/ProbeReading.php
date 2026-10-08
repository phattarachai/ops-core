<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

use DateTimeImmutable;

final readonly class ProbeReading
{
    public function __construct(
        public ProbeLevel $level,
        public DateTimeImmutable $at,
        public ?float $value = null,
        public ?string $message = null,
    ) {}
}
