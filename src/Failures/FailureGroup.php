<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Failures;

use DateTimeImmutable;

final readonly class FailureGroup
{
    /** @param  non-empty-list<Failure>  $failures */
    public function __construct(
        public DateTimeImmutable $startedAt,
        public DateTimeImmutable $endedAt,
        public array $failures,
    ) {}

    public function first(): Failure
    {
        return $this->failures[0];
    }

    public function count(): int
    {
        return count($this->failures);
    }

    public function longestSeconds(): ?float
    {
        $seconds = array_filter(array_map(static fn (Failure $failure): ?float => $failure->seconds, $this->failures), is_float(...));

        return $seconds === [] ? null : max($seconds);
    }

    /** @return list<string> */
    public function domains(): array
    {
        $domains = array_map(static fn (Failure $failure): ?string => $failure->domain, $this->failures);

        return array_values(array_unique(array_filter($domains, static fn (?string $domain): bool => $domain !== null && $domain !== '')));
    }

    public function with(Failure $failure): self
    {
        return new self($this->startedAt, $failure->at, [...$this->failures, $failure]);
    }
}
