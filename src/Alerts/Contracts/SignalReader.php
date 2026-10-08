<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts\Contracts;

use DateTimeImmutable;
use Phattarachai\OpsCore\Alerts\ProbeReading;

interface SignalReader
{
    /** @return array<string, int> occurrences per key in [$from, $to) */
    public function countsByKey(string $signal, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /** @return array<string, int> distinct values of $field per key in [$from, $to) */
    public function distinctCountsByKey(string $signal, string $field, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /** @return array<string, DateTimeImmutable> */
    public function lastSeenByKey(string $signal): array;

    /** @return array<string, ProbeReading> */
    public function statesByKey(string $signal): array;
}
