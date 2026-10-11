# Ops Core

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phattarachai/ops-core.svg?style=flat-square)](https://packagist.org/packages/phattarachai/ops-core)
[![Tests](https://img.shields.io/github/actions/workflow/status/phattarachai/ops-core/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/phattarachai/ops-core/actions/workflows/run-tests.yml?query=branch%3Amain)
[![Code Style](https://img.shields.io/github/actions/workflow/status/phattarachai/ops-core/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/phattarachai/ops-core/actions/workflows/fix-php-code-style-issues.yml?query=branch%3Amain)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/phattarachai/ops-core/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/phattarachai/ops-core/actions/workflows/phpstan.yml?query=branch%3Amain)
[![PHP Version](https://img.shields.io/packagist/dependency-v/phattarachai/ops-core/php?style=flat-square&label=php&logo=php&logoColor=white)](https://packagist.org/packages/phattarachai/ops-core)
[![Total Downloads](https://img.shields.io/packagist/dt/phattarachai/ops-core.svg?style=flat-square)](https://packagist.org/packages/phattarachai/ops-core)

Framework-free core of the Ops monitoring suite: rollup math, latency histograms, alert rule evaluation and incident
lifecycle. Pure PHP — no Laravel container, no service provider, nothing to register. The Laravel integration lives in
[`phattarachai/ops-laravel`](https://github.com/phattarachai/ops-laravel).

> **Status: 0.x.** The API can still change between minor versions until `v1.0`.

## Install

```bash
composer require phattarachai/ops-core
```

## What's inside

| Namespace | What it does |
|---|---|
| `Histogram\LatencyHistogram` | 13 fixed latency bands (0.1 s … 300 s + open tail). Histograms merge by summing, so a percentile over any window comes from the merged bands — never from averaging per-bucket percentiles. |
| `Rollup\Bucket`, `Rollup\Grain` | A mergeable aggregate (count, sum, max, histogram, named counters) and minute / hour / day truncation. Fold minute buckets into hours with `Bucket::sum()`. |
| `Time\WindowPlan` | Which grain a reporting window reads and how many display bars it gets, from the span alone. |
| `Health\HealthClassifier` | Baseline-relative health: a jump against the subject's own trailing baseline is an `Incident`, a constant failure is `KnownBroken`, and an absolute floor (20 % by default) stops a chronically failing subject from ever reading healthy. Thresholds live in `HealthPolicy`. |
| `Failures\FailureGrouper` | Folds failed runs or calls into incidents: same exception and message (numbers masked), each within 3 minutes of the last. |
| `Schedule\MissedTicks` | Counts cron ticks no run started within the grace window after. |
| `Alerts\*` | Rule evaluation and the incident lifecycle (below). |

### Alerts

A `RuleSpec` is one of four types:

| Type | Fires when | `threshold` means |
|---|---|---|
| `threshold` | a signal's count per key (or distinct `distinctField` values per key) reaches N within `windowSeconds` | N |
| `rate` | `signal` ÷ `denominatorSignal` per key reaches a percentage, given at least `minSamples` | percent |
| `absence` | a key has not been seen for X seconds (heartbeats, schedules) | seconds |
| `state` | a probe's latest reading reaches `minLevel` (warning / critical) | — |

`AlertEngine::runRule()` evaluates one rule and reconciles its incidents (one per rule × key):

- **open** on the first breach and notify;
- **escalate** at once when severity rises; otherwise **remind** once `cooldownSeconds` have passed since the last notice;
- **resolve** once the rule has stayed clear for `resolveAfterSeconds`, and say so if anyone was told it opened;
- a **muted** rule keeps tracking incidents but sends nothing until the mute ends; a rule switched off, or outside its
  `environments`, resolves its open incidents silently.

Storage and delivery sit behind three interfaces the host implements: `SignalReader` (counts, distinct counts, last
seen, probe states), `IncidentStore`, and `IncidentChannel` (mail, Slack, …). The engine returns `IncidentNotice`s;
sending them is the caller's job.

## Development

```bash
composer install
vendor/bin/pest              # tests, including the arch test that keeps Illuminate out of src/
vendor/bin/pint              # code style
vendor/bin/phpstan analyse   # static analysis, level max
```

## License

MIT. See [LICENSE](LICENSE).

## ผู้พัฒนา

พัฒนาและดูแลโดย [บริษัท ภัทรชัย อาร์ทิซาน จำกัด](https://phattarachai.dev) (Phattarachai Artisan)
บริษัทที่ปรึกษาและพัฒนาเว็บ ที่เรียนรู้และแบ่งปันกับชุมชน Laravel
แพ็กเกจนี้แบ่งปันให้ชุมชนนำไปใช้และต่อยอดได้อย่างอิสระ

ดูแพ็กเกจอื่นของเราได้ที่ [phattarachai.dev/open-source](https://phattarachai.dev/open-source)
และติดต่อเราได้ที่ [phattarachai.dev](https://phattarachai.dev/#contact)
