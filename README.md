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

> **Status: pre-release, under construction.** Nothing is tagged yet and the API will change without notice until
> `v0.1.0`. Don't depend on it in production.

## Install

```bash
composer require phattarachai/ops-core
```

## Development

```bash
composer install
vendor/bin/pest              # tests, including the arch test that keeps Illuminate out of src/
vendor/bin/pint              # code style
vendor/bin/phpstan analyse   # static analysis, level max
```

## License

MIT. See [LICENSE](LICENSE).
