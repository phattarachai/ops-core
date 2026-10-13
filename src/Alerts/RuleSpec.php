<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

use DateTimeImmutable;

/**
 * A stored alert rule, framework-free. `threshold` is a count (threshold), a percentage (rate)
 * or seconds since last seen (absence); state rules compare against `minLevel` instead.
 * `remindWarnings: false` keeps reminders for critical incidents only.
 */
final readonly class RuleSpec
{
    /**
     * @param  list<string>  $keys  only these keys; empty = every key
     * @param  list<string>  $environments  empty = every environment
     * @param  list<string>  $channels
     */
    public function __construct(
        public int|string $id,
        public string $name,
        public RuleType $type,
        public string $signal,
        public float $threshold = 0.0,
        public int $windowSeconds = 300,
        public Severity $severity = Severity::Warning,
        public ?string $denominatorSignal = null,
        public int $minSamples = 1,
        public ?string $distinctField = null,
        public array $keys = [],
        public ProbeLevel $minLevel = ProbeLevel::Warning,
        public int $cooldownSeconds = 900,
        public int $resolveAfterSeconds = 300,
        public array $environments = [],
        public array $channels = [],
        public bool $active = true,
        public ?DateTimeImmutable $mutedUntil = null,
        public bool $notifyOnResolve = true,
        public bool $remindWarnings = true,
    ) {}

    public function isMuted(DateTimeImmutable $now): bool
    {
        return $this->mutedUntil !== null && $this->mutedUntil > $now;
    }

    public function appliesTo(?string $environment): bool
    {
        return $this->environments === [] || in_array($environment, $this->environments, true);
    }

    public function allowsKey(string $key): bool
    {
        return $this->keys === [] || in_array($key, $this->keys, true);
    }

    public function windowStart(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify("-{$this->windowSeconds} seconds");
    }
}
