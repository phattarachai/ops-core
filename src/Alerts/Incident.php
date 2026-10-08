<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

use DateTimeImmutable;

final readonly class Incident
{
    /** @param  array<string, mixed>  $context */
    public function __construct(
        public int|string $ruleId,
        public string $key,
        public Severity $severity,
        public DateTimeImmutable $openedAt,
        public DateTimeImmutable $lastBreachedAt,
        public float $peakValue,
        public float $lastValue,
        public IncidentStatus $status = IncidentStatus::Open,
        public ?DateTimeImmutable $lastNotifiedAt = null,
        public ?DateTimeImmutable $resolvedAt = null,
        public int $notifications = 0,
        public array $context = [],
        public int|string|null $id = null,
    ) {}

    public static function open(int|string $ruleId, Breach $breach, DateTimeImmutable $now): self
    {
        return new self($ruleId, $breach->key, $breach->severity, $now, $now, $breach->value, $breach->value, context: $breach->context);
    }

    public function isOpen(): bool
    {
        return $this->status === IncidentStatus::Open;
    }

    public function breached(Breach $breach, DateTimeImmutable $now): self
    {
        return new self(
            $this->ruleId,
            $this->key,
            $breach->severity->outranks($this->severity) ? $breach->severity : $this->severity,
            $this->openedAt,
            $now,
            max($this->peakValue, $breach->value),
            $breach->value,
            $this->status,
            $this->lastNotifiedAt,
            $this->resolvedAt,
            $this->notifications,
            $breach->context,
            $this->id,
        );
    }

    public function notified(DateTimeImmutable $now): self
    {
        return new self(
            $this->ruleId, $this->key, $this->severity, $this->openedAt, $this->lastBreachedAt, $this->peakValue, $this->lastValue,
            $this->status, $now, $this->resolvedAt, $this->notifications + 1, $this->context, $this->id,
        );
    }

    public function resolved(DateTimeImmutable $now): self
    {
        return new self(
            $this->ruleId, $this->key, $this->severity, $this->openedAt, $this->lastBreachedAt, $this->peakValue, $this->lastValue,
            IncidentStatus::Resolved, $this->lastNotifiedAt, $now, $this->notifications, $this->context, $this->id,
        );
    }

    public function withId(int|string $id): self
    {
        return new self(
            $this->ruleId, $this->key, $this->severity, $this->openedAt, $this->lastBreachedAt, $this->peakValue, $this->lastValue,
            $this->status, $this->lastNotifiedAt, $this->resolvedAt, $this->notifications, $this->context, $id,
        );
    }

    public function secondsSinceNotified(DateTimeImmutable $now): ?int
    {
        return $this->lastNotifiedAt === null ? null : $now->getTimestamp() - $this->lastNotifiedAt->getTimestamp();
    }

    public function secondsSinceBreached(DateTimeImmutable $now): int
    {
        return $now->getTimestamp() - $this->lastBreachedAt->getTimestamp();
    }
}
