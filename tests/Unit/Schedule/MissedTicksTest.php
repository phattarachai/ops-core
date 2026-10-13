<?php

declare(strict_types=1);

use Phattarachai\OpsCore\Schedule\MissedTicks;

it('counts ticks no run started within the grace after', function (): void {
    $ticks = [0, 600, 1200, 1800];
    $seen = [5, 1210, 1600];

    expect(MissedTicks::count($ticks, $seen, graceSeconds: 300))->toBe(2);
});

it('lets one run cover one tick only', function (): void {
    expect(MissedTicks::count([0, 60], [10], graceSeconds: 300))->toBe(1);
});

it('treats a skip row like a run', function (): void {
    expect(MissedTicks::count([0], [0]))->toBe(0);
});

it('never counts a tick inside an excused window', function (): void {
    $ticks = [0, 60, 120, 180];
    $seen = [2, 182];

    expect(MissedTicks::count($ticks, $seen, graceSeconds: 30, excused: [[50, 130]]))->toBe(0)
        ->and(MissedTicks::count($ticks, $seen, graceSeconds: 30, excused: [[50, 100]]))->toBe(1)
        ->and(MissedTicks::count($ticks, $seen, graceSeconds: 30))->toBe(2);
});
