<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Tests\Support;

use DateTimeImmutable;
use Phattarachai\OpsCore\Alerts\Contracts\SignalReader;
use Phattarachai\OpsCore\Alerts\ProbeReading;

final class FakeSignalReader implements SignalReader
{
    /** @var array<string, list<array{at: DateTimeImmutable, key: string, meta: array<string, string>}>> */
    public array $events = [];

    /** @var array<string, array<string, ProbeReading>> */
    public array $states = [];

    /** @param  array<string, string>  $meta */
    public function emit(string $signal, string $key, DateTimeImmutable $at, int $times = 1, array $meta = []): self
    {
        foreach (range(1, $times) as $ignored) {
            $this->events[$signal][] = ['at' => $at, 'key' => $key, 'meta' => $meta];
        }

        return $this;
    }

    public function countsByKey(string $signal, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $counts = [];

        foreach ($this->within($signal, $from, $to) as $event) {
            $counts[$event['key']] = ($counts[$event['key']] ?? 0) + 1;
        }

        return $counts;
    }

    public function distinctCountsByKey(string $signal, string $field, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $values = [];

        foreach ($this->within($signal, $from, $to) as $event) {
            $values[$event['key']][$event['meta'][$field] ?? ''] = true;
        }

        return array_map(count(...), $values);
    }

    public function lastSeenByKey(string $signal): array
    {
        $last = [];

        foreach ($this->events[$signal] ?? [] as $event) {
            $last[$event['key']] = max($last[$event['key']] ?? $event['at'], $event['at']);
        }

        return $last;
    }

    public function statesByKey(string $signal): array
    {
        return $this->states[$signal] ?? [];
    }

    /** @return list<array{at: DateTimeImmutable, key: string, meta: array<string, string>}> */
    private function within(string $signal, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return array_values(array_filter(
            $this->events[$signal] ?? [],
            static fn (array $event): bool => $event['at'] >= $from && $event['at'] < $to,
        ));
    }
}
