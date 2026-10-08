<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Time;

use Phattarachai\OpsCore\Rollup\Grain;

/**
 * Which rollup grain a reporting window reads and how it splits into display bars. Derived from
 * the span alone, so a preset and a custom range of the same length read identically.
 */
final readonly class WindowPlan
{
    public const int MAX_BARS = 48;

    public function __construct(
        public Grain $grain,
        public int $stepMinutes,
        public int $bars,
    ) {}

    public static function forSpan(int $minutes): self
    {
        $span = max(1, $minutes);

        [$grain, $step] = match (true) {
            $span <= 120 => [Grain::Minute, 5],
            $span <= 720 => [Grain::Minute, 15],
            $span <= 2880 => [Grain::Hour, 60],
            $span <= 14400 => [Grain::Hour, 360],
            default => [Grain::Hour, 1440],
        };

        return new self($grain, $step, min(self::MAX_BARS, max(1, (int) ceil($span / $step))));
    }

    public function usesDateLabels(): bool
    {
        return $this->stepMinutes >= 360;
    }
}
