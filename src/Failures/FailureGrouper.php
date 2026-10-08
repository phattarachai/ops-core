<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Failures;

/**
 * Turns failed runs or calls into one row per incident: same exception basename and message
 * (numbers masked), each failure within the gap of the group's last one.
 */
final readonly class FailureGrouper
{
    public function __construct(private int $gapSeconds = 180) {}

    /**
     * @param  iterable<Failure>  $failures
     * @return list<FailureGroup> newest first
     */
    public function group(iterable $failures): array
    {
        $groups = [];
        $open = [];

        foreach ($this->chronological($failures) as $failure) {
            $key = $failure->groupKey();
            $index = $open[$key] ?? null;

            if ($index !== null && $this->joins($groups[$index], $failure)) {
                $groups[$index] = $groups[$index]->with($failure);

                continue;
            }

            $groups[] = new FailureGroup($failure->at, $failure->at, [$failure]);
            $open[$key] = array_key_last($groups);
        }

        usort($groups, static fn (FailureGroup $a, FailureGroup $b): int => $b->startedAt <=> $a->startedAt);

        return $groups;
    }

    private function joins(FailureGroup $group, Failure $failure): bool
    {
        return abs($failure->at->getTimestamp() - $group->endedAt->getTimestamp()) <= $this->gapSeconds;
    }

    /**
     * @param  iterable<Failure>  $failures
     * @return list<Failure>
     */
    private function chronological(iterable $failures): array
    {
        $sorted = [...$failures];
        usort($sorted, static fn (Failure $a, Failure $b): int => $a->at <=> $b->at);

        return $sorted;
    }
}
