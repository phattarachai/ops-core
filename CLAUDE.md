# Ops Core — agent guide

## Scope

Framework-free core of the Ops monitoring suite: rollup math, latency histograms, alert rule evaluation and incident
lifecycle. The Laravel side — recorders, storage, queue/schedule wiring, the Inertia dashboard, mail and Slack
delivery — lives in [`phattarachai/ops-laravel`](https://github.com/phattarachai/ops-laravel) (sibling checkout at
`../ops-laravel`), which consumes this package.

## Rules

- **No `illuminate/*`, ever.** No `config()`, `app()`, `now()`, facades or collections. Everything arrives through
  constructor or method arguments; time comes in as a value (`DateTimeImmutable` / int timestamps), never read from a
  clock inside a calculation. `tests/Unit/ArchTest.php` enforces the no-Illuminate rule and `declare(strict_types=1)`.
- Pure and deterministic: given the same inputs, a rollup, histogram or rule evaluation returns the same output. No I/O.
- Any logic both the Laravel package and a future non-Laravel consumer would need goes here first (see "Core first" in
  ops-laravel's `CLAUDE.md`).
- A change here ships as a core release (tag + GitHub release, which pings Packagist), then ops-laravel bumps
  `phattarachai/ops-core`.

## Tests and checks

```bash
composer install
vendor/bin/pest              # composer test
vendor/bin/pint              # composer format
vendor/bin/phpstan analyse   # composer analyse — level max, src/ only (Pest's arch DSL is not PHPStan-friendly)
```
