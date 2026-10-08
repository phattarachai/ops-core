<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Rollup;

use Phattarachai\OpsCore\Histogram\LatencyHistogram;

/**
 * A mergeable aggregate of timed samples: count, latency sum/max, the latency histogram and
 * free-form counters (status classes, outcome kinds). Percentiles come from the merged bands.
 */
final readonly class Bucket
{
    /**
     * @param  list<int>  $histogram
     * @param  array<string, int>  $counters
     */
    public function __construct(
        public int $count = 0,
        public float $sumSeconds = 0.0,
        public ?float $maxSeconds = null,
        public array $histogram = [],
        public array $counters = [],
    ) {}

    /** @param  array<string, int>  $counters */
    public static function sample(float $seconds, array $counters = []): self
    {
        $bin = LatencyHistogram::bin($seconds);
        $histogram = array_map(static fn (int $index): int => $index === $bin ? 1 : 0, range(0, LatencyHistogram::BIN_COUNT - 1));

        return new self(1, $seconds, $seconds, $histogram, $counters);
    }

    /** @param  iterable<self>  $buckets */
    public static function sum(iterable $buckets): self
    {
        $total = new self;

        foreach ($buckets as $bucket) {
            $total = $total->merge($bucket);
        }

        return $total;
    }

    public function merge(self $other): self
    {
        return new self(
            $this->count + $other->count,
            $this->sumSeconds + $other->sumSeconds,
            $this->maxOf($other->maxSeconds),
            LatencyHistogram::merge($this->histogram, $other->histogram),
            $this->mergeCounters($other->counters),
        );
    }

    public function counter(string $name): int
    {
        return $this->counters[$name] ?? 0;
    }

    public function percentile(float $quantile): ?float
    {
        $value = LatencyHistogram::percentile($this->histogram, $quantile, $this->maxSeconds);

        return $value === null || $this->maxSeconds === null ? $value : min($value, $this->maxSeconds);
    }

    public function p50(): ?float
    {
        return $this->percentile(0.5);
    }

    public function p95(): ?float
    {
        return $this->percentile(0.95);
    }

    public function averageSeconds(): ?float
    {
        return $this->count > 0 ? $this->sumSeconds / $this->count : null;
    }

    private function maxOf(?float $other): ?float
    {
        return match (true) {
            $this->maxSeconds === null => $other,
            $other === null => $this->maxSeconds,
            default => max($this->maxSeconds, $other),
        };
    }

    /**
     * @param  array<string, int>  $other
     * @return array<string, int>
     */
    private function mergeCounters(array $other): array
    {
        $merged = $this->counters;

        foreach ($other as $name => $value) {
            $merged[$name] = ($merged[$name] ?? 0) + $value;
        }

        return $merged;
    }
}
