<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Rollup;

use DateTimeImmutable;

enum Grain: string
{
    case Minute = 'minute';
    case Hour = 'hour';
    case Day = 'day';

    public function seconds(): int
    {
        return match ($this) {
            self::Minute => 60,
            self::Hour => 3600,
            self::Day => 86400,
        };
    }

    public function truncate(DateTimeImmutable $at): DateTimeImmutable
    {
        return match ($this) {
            self::Minute => $at->setTime((int) $at->format('G'), (int) $at->format('i')),
            self::Hour => $at->setTime((int) $at->format('G'), 0),
            self::Day => $at->setTime(0, 0),
        };
    }
}
