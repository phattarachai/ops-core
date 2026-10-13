<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Schedule;

/**
 * Counts cron ticks no run (or skip) started within the grace window after. Both lists are
 * sorted unix timestamps; each run covers one tick at most. Ticks inside an excused window
 * (a deploy, say) never count, whether or not a run covered them.
 */
final class MissedTicks
{
    /**
     * @param  list<int>  $ticks
     * @param  list<int>  $seen
     * @param  list<array{int, int}>  $excused  inclusive [from, to] unix timestamps
     */
    public static function count(array $ticks, array $seen, int $graceSeconds = 300, array $excused = []): int
    {
        $missed = 0;
        $cursor = 0;
        $seenCount = count($seen);

        foreach ($ticks as $tick) {
            if (self::isExcused($tick, $excused)) {
                continue;
            }

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

    /** @param  list<array{int, int}>  $excused */
    private static function isExcused(int $tick, array $excused): bool
    {
        foreach ($excused as [$from, $to]) {
            if ($tick >= $from && $tick <= $to) {
                return true;
            }
        }

        return false;
    }
}
