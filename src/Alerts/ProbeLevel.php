<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

enum ProbeLevel: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Critical = 'critical';

    public function rank(): int
    {
        return match ($this) {
            self::Ok => 0,
            self::Warning => 1,
            self::Critical => 2,
        };
    }

    public function reaches(self $level): bool
    {
        return $this !== self::Ok && $this->rank() >= $level->rank();
    }

    public function severity(): Severity
    {
        return $this === self::Critical ? Severity::Critical : Severity::Warning;
    }
}
