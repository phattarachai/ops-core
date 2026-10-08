<?php

declare(strict_types=1);

use Phattarachai\OpsCore\Histogram\LatencyHistogram;

it('bins a latency into the first band it falls under', function (float $seconds, int $bin): void {
    expect(LatencyHistogram::bin($seconds))->toBe($bin);
})->with([
    'under 0.1' => [0.05, 0],
    'lower edge is inclusive' => [0.1, 1],
    '[0.5, 1.0)' => [0.5, 3],
    '[8, 15)' => [8.0, 7],
    'open tail at the edge' => [300.0, 12],
    'open tail well past the edge' => [999.0, 12],
]);

it('bins milliseconds on the same bands', function (): void {
    expect(LatencyHistogram::binMs(750))->toBe(3);
});

it('merges histograms band by band', function (): void {
    expect(LatencyHistogram::merge([0 => 1, 3 => 4], [3 => 2, 12 => 5]))
        ->toHaveCount(LatencyHistogram::BIN_COUNT)
        ->toMatchArray([0 => 1, 3 => 6, 12 => 5]);
});

it('interpolates a percentile inside the band holding the target rank', function (array $bins, float $quantile, ?float $max, ?float $expected): void {
    expect(LatencyHistogram::percentile($bins, $quantile, $max))->toBe($expected);
})->with([
    'empty histogram' => [[], 0.5, null, null],
    'p50 inside one band' => [[3 => 10], 0.5, null, 0.75],
    'p95 inside one band' => [[3 => 10], 0.95, null, 0.975],
    'open tail uses the max as its upper edge' => [[12 => 4], 0.5, 305.0, 302.5],
    'p50 on a band boundary' => [[0 => 5, 3 => 5], 0.5, null, 0.1],
    'p90 crosses into the next band' => [[0 => 5, 3 => 5], 0.9, null, 0.9],
]);
