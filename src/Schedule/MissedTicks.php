<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Schedule;

/**
 * Counts cron ticks no run (or skip) started within the grace window after. Both lists are
 * sorted unix timestamps; each run covers one tick at most.
 */
final class MissedTicks
{
    /**
     * @param  list<int>  $ticks
     * @param  list<int>  $seen
     */
    public static function count(array $ticks, array $seen, int $graceSeconds = 300): int
    {
        $missed = 0;
        $cursor = 0;
        $seenCount = count($seen);

        foreach ($ticks as $tick) {
            while ($cursor < $seenCount && $seen[$cursor] < $tick) {
                $cursor++;
            }

            if ($cursor >= $seenCount || $seen[$cursor] >= $tick + $graceSeconds) {
                $missed++;

                continue;
            }

            $cursor++;
        }

        return $missed;
    }
}
