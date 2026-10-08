<?php

declare(strict_types=1);

use Phattarachai\OpsCore\Failures\Failure;
use Phattarachai\OpsCore\Failures\FailureGrouper;

function failureAt(string $at, string $message, ?string $domain = null): Failure
{
    return new Failure(new DateTimeImmutable($at), 'Illuminate\Http\Client\ConnectionException', $message, 180.0, $domain);
}

it('folds one burst of the same failure into a single group', function (): void {
    $groups = new FailureGrouper()->group([
        failureAt('2026-09-28 12:02:24', 'cURL error 28: Operation timed out after 180001 milliseconds', '0010000010'),
        failureAt('2026-09-28 12:02:24', 'cURL error 28: Operation timed out after 180005 milliseconds', '0010002808'),
        failureAt('2026-09-28 12:03:10', 'cURL error 28: Operation timed out after 180000 milliseconds', '0010000010'),
    ]);

    expect($groups)->toHaveCount(1)
        ->and($groups[0]->count())->toBe(3)
        ->and($groups[0]->endedAt->format('H:i:s'))->toBe('12:03:10')
        ->and($groups[0]->domains())->toBe(['0010000010', '0010002808'])
        ->and($groups[0]->longestSeconds())->toBe(180.0)
        ->and(Failure::basename((string) $groups[0]->first()->exception))->toBe('ConnectionException');
});

it('splits groups by message and by a quiet gap, newest first', function (): void {
    $groups = new FailureGrouper()->group([
        failureAt('2026-09-27 12:00:05', 'cURL error 28: Connection timeout after 10000 ms'),
        failureAt('2026-09-27 12:00:40', 'cURL error 28: Operation timed out after 180001 milliseconds'),
        failureAt('2026-09-28 12:02:24', 'cURL error 28: Operation timed out after 180001 milliseconds'),
    ]);

    expect(array_map(fn ($group): string => $group->startedAt->format('d H:i'), $groups))->toBe(['28 12:02', '27 12:00', '27 12:00']);
});
