<?php

declare(strict_types=1);

use Phattarachai\OpsCore\Rollup\Grain;
use Phattarachai\OpsCore\Time\WindowPlan;

it('derives grain, step and bars from the span', function (int $minutes, Grain $grain, int $step, int $bars): void {
    expect(WindowPlan::forSpan($minutes))
        ->grain->toBe($grain)
        ->stepMinutes->toBe($step)
        ->bars->toBe($bars);
})->with([
    '1h' => [60, Grain::Minute, 5, 12],
    '6h' => [360, Grain::Minute, 15, 24],
    '24h' => [1440, Grain::Hour, 60, 24],
    '7d' => [10080, Grain::Hour, 360, 28],
    '30d' => [43200, Grain::Hour, 1440, 30],
    'a 90-day range caps the bars' => [129600, Grain::Hour, 1440, 48],
]);
