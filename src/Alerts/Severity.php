<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

enum Severity: string
{
    case Warning = 'warning';
    case Critical = 'critical';

    public function rank(): int
    {
        return match ($this) {
            self::Warning => 1,
            self::Critical => 2,
        };
    }

    public function outranks(self $other): bool
    {
        return $this->rank() > $other->rank();
    }
}
