# Static Analysis (Larastan)

This repository uses Larastan (PHPStan for Laravel) to catch type issues and unsafe usage during development.

## Run analysis

```bash
composer stan
```

## Generate or refresh baseline

```bash
composer stan:baseline
```

Notes:
- Configuration lives in `phpstan.neon`.
- The baseline file is `larastan-baseline.neon`.
