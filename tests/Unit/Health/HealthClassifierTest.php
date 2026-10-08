<?php

declare(strict_types=1);

use Phattarachai\OpsCore\Health\Health;
use Phattarachai\OpsCore\Health\HealthClassifier;
use Phattarachai\OpsCore\Health\HealthInput;

it('classifies against the subject\'s own baseline', function (HealthInput $input, Health $expected): void {
    expect(new HealthClassifier()->classify($input)->health)->toBe($expected);
})->with([
    'no calls' => [new HealthInput(0, 0), Health::NoData],
    'flat and clean' => [new HealthInput(100, 1, baselineErrorPct: 1.0), Health::Ok],
    'a jump past 10 points is an incident' => [new HealthInput(100, 15, baselineErrorPct: 2.0), Health::Incident],
    'a jump past 3 points degrades' => [new HealthInput(100, 6, baselineErrorPct: 2.0), Health::Degraded],
    'a constant 99% failure is known broken, not news' => [new HealthInput(100, 99, baselineErrorPct: 99.0), Health::KnownBroken],
    'the chronic floor overrides a flat baseline' => [new HealthInput(100, 31, baselineErrorPct: 30.9), Health::KnownBroken],
    'under the floor with a flat baseline stays healthy' => [new HealthInput(100, 15, baselineErrorPct: 15.0), Health::Ok],
    'pinned at the ceiling with a jump is an incident even when known broken' => [new HealthInput(100, 10, p95: 300.0, ceilingSeconds: 300.0, baselineErrorPct: 2.0, knownBroken: true), Health::Incident],
    'a slow median degrades' => [new HealthInput(100, 0, p50: 6.0, ceilingSeconds: 10.0, baselineErrorPct: 0.0), Health::Degraded],
]);

it('reports the deviation in points and whether p95 is pinned', function (): void {
    $verdict = new HealthClassifier()->classify(new HealthInput(200, 30, p95: 61.0, ceilingSeconds: 60.0, baselineErrorPct: 5.0));

    expect($verdict)
        ->errorPct->toBe(15.0)
        ->deviation->toBe(10.0)
        ->pinnedAtCeiling->toBeTrue();
});

it('rates job classes by their failure share', function (int $runs, int $failed, Health $expected): void {
    expect(new HealthClassifier()->forFailureRate($runs, $failed))->toBe($expected);
})->with([
    [0, 0, Health::NoData],
    [50, 0, Health::Ok],
    [50, 2, Health::Degraded],
    [50, 10, Health::Incident],
]);
