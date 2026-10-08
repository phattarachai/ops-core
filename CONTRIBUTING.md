# Contributing

Thanks for taking the time. Bug reports, fixes and small focused features are all welcome.

## Getting set up

```bash
git clone https://github.com/phattarachai/ops-core.git
cd ops-core
composer install
vendor/bin/pest
```

Core is plain PHP: no Laravel, no container, nothing to boot. The suite runs straight from `vendor/bin/pest`.

## Before opening a pull request

- **Add or update a test.** A change without a test that would have caught the bug is hard to keep working.
- **Run the checks:** `vendor/bin/pest`, `vendor/bin/pint`, `vendor/bin/phpstan analyse`.
- **Keep the diff to one thing.** Unrelated refactors and reformatting make review slow; open a second PR for them.
- **Write the PR title as a changelog line** — `fix: ...`, `feat: ...`, `docs: ...`. Release notes are generated from
  those titles, so the title is what users end up reading.

CI runs the suite with both the lowest and the latest allowed dependencies.
A red `prefer-lowest` job usually means a version constraint in `composer.json` needs raising.

## Reporting a bug

Open an issue with the package version, PHP version, and the smallest reproduction you can manage.
Security problems go through [SECURITY.md](SECURITY.md) instead — please do not open a public issue for those.
