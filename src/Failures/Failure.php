<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Failures;

use DateTimeImmutable;

final readonly class Failure
{
    public function __construct(
        public DateTimeImmutable $at,
        public ?string $exception = null,
        public ?string $message = null,
        public ?float $seconds = null,
        public ?string $domain = null,
    ) {}

    public function groupKey(): string
    {
        return self::basename((string) $this->exception).'|'.self::normalizeMessage($this->message);
    }

    public static function normalizeMessage(?string $message): string
    {
        $normalized = trim((string) preg_replace('/\d+(\.\d+)?/', '#', (string) $message));

        return mb_strlen($normalized) > 120 ? mb_substr($normalized, 0, 120).'...' : $normalized;
    }

    public static function basename(string $class): string
    {
        $position = strrpos($class, '\\');

        return $position === false ? $class : substr($class, $position + 1);
    }
}
