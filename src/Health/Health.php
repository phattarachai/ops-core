<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Health;

enum Health: string
{
    case Incident = 'incident';
    case Degraded = 'degraded';
    case KnownBroken = 'known_broken';
    case Ok = 'ok';
    case NoData = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Incident => 'Incident',
            self::Degraded => 'Degrading',
            self::KnownBroken => 'Known broken',
            self::Ok => 'Healthy',
            self::NoData => 'No data',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Incident => 'danger',
            self::Degraded => 'warning',
            self::KnownBroken, self::NoData => 'gray',
            self::Ok => 'success',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Incident => 0,
            self::Degraded => 1,
            self::KnownBroken => 2,
            self::Ok => 3,
            self::NoData => 4,
        };
    }
}
