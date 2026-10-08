<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Histogram;

final class LatencyHistogram
{
    /** @var list<float> exclusive upper bound of each band, in seconds; one open band follows the last */
    public const array BOUNDS = [0.1, 0.25, 0.5, 1.0, 2.0, 4.0, 8.0, 15.0, 30.0, 60.0, 120.0, 300.0];

    public const int BIN_COUNT = 13;

    public static function bin(float $seconds): int
    {
        foreach (self::BOUNDS as $index => $upper) {
            if ($seconds < $upper) {
                return $index;
            }
        }

        return self::BIN_COUNT - 1;
    }

    public static function binMs(float $milliseconds): int
    {
        return self::bin($milliseconds / 1000);
    }

    /** @return list<int> */
    public static function empty(): array
    {
        return array_fill(0, self::BIN_COUNT, 0);
    }

    /**
     * @param  array<int|string, int|float|string|null>  $a
     * @param  array<int|string, int|float|string|null>  $b
     * @return list<int>
     */
    public static function merge(array $a, array $b): array
    {
        return array_map(
            static fn (int $left, int $right): int => $left + $right,
            self::normalize($a),
            self::normalize($b),
        );
    }

    /**
     * Interpolated percentile in seconds; the open band uses $max as its upper edge.
     *
     * @param  array<int|string, int|float|string|null>  $bins
     */
    public static function percentile(array $bins, float $quantile, ?float $max = null): ?float
    {
        $bins = self::normalize($bins);
        $total = array_sum($bins);

        if ($total === 0) {
            return null;
        }

        $target = $quantile * $total;
        $cumulative = 0;

        foreach ($bins as $index => $count) {
            if ($count === 0) {
                continue;
            }

            if ($cumulative + $count >= $target) {
                return self::interpolate($index, ($target - $cumulative) / $count, $max);
            }

            $cumulative += $count;
        }

        return $max;
    }

    /**
     * @param  array<int|string, int|float|string|null>  $bins
     * @return list<int>
     */
    public static function normalize(array $bins): array
    {
        return array_map(
            static fn (int $index): int => (int) ($bins[$index] ?? 0),
            range(0, self::BIN_COUNT - 1),
        );
    }

    private static function interpolate(int $index, float $fraction, ?float $max): float
    {
        $lower = self::BOUNDS[$index - 1] ?? 0.0;
        $upper = self::BOUNDS[$index] ?? max($max ?? $lower, $lower);

        return round($lower + $fraction * ($upper - $lower), 3);
    }
}
