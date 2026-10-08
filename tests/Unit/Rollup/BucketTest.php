<?php

declare(strict_types=1);

use Phattarachai\OpsCore\Rollup\Bucket;
use Phattarachai\OpsCore\Rollup\Grain;

it('folds samples into one mergeable bucket', function (): void {
    $bucket = Bucket::sum([
        Bucket::sample(0.2, ['2xx' => 1]),
        Bucket::sample(0.6, ['2xx' => 1]),
        Bucket::sample(9.0, ['5xx' => 1]),
    ]);

    expect($bucket)
        ->count->toBe(3)
        ->maxSeconds->toBe(9.0)
        ->and($bucket->counter('2xx'))->toBe(2)
        ->and($bucket->counter('5xx'))->toBe(1)
        ->and($bucket->counter('429'))->toBe(0)
        ->and($bucket->averageSeconds())->toBe(9.8 / 3);
});

it('merges minute buckets into the same percentiles as one big bucket', function (): void {
    $samples = [0.05, 0.3, 0.3, 0.7, 1.5, 3.0, 3.5, 12.0];
    $minutes = [Bucket::sum(array_map(Bucket::sample(...), array_slice($samples, 0, 4))), Bucket::sum(array_map(Bucket::sample(...), array_slice($samples, 4)))];

    $hour = Bucket::sum($minutes);
    $direct = Bucket::sum(array_map(Bucket::sample(...), $samples));

    expect($hour->p50())->toBe($direct->p50())
        ->and($hour->p95())->toBe($direct->p95());
});

it('never reports a percentile above the observed max', function (): void {
    expect(Bucket::sample(0.12)->p95())->toBe(0.12);
});

it('has no percentile when empty', function (): void {
    expect(new Bucket()->p50())->toBeNull()
        ->and(new Bucket()->averageSeconds())->toBeNull();
});

it('truncates a moment to its grain', function (Grain $grain, string $expected): void {
    expect($grain->truncate(new DateTimeImmutable('2026-10-08 14:37:52'))->format('Y-m-d H:i:s'))->toBe($expected);
})->with([
    [Grain::Minute, '2026-10-08 14:37:00'],
    [Grain::Hour, '2026-10-08 14:00:00'],
    [Grain::Day, '2026-10-08 00:00:00'],
]);
