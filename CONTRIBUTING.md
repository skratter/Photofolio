# Contributing

## Setup

```bash
composer setup   # composer install, .env, app key, migrate, npm install + build
composer dev      # runs server, queue listener and vite dev server concurrently
```

Requires PHP ^8.5 and Node 22.

## Stack

Laravel 13, Livewire 4 + Flux UI (free tier), Pest 4, Pint, Larastan (PHPStan level via `larastan/larastan`). This project is set up with [Laravel Boost](https://github.com/laravel/boost); `CLAUDE.md` and `.claude/skills` document the conventions below in more detail and are kept up to date automatically by Boost.

## Before opening a PR

```bash
composer test   # config:clear, pint --test, phpstan, pest
```

Or individually:

```bash
vendor/bin/pint --dirty          # fix code style on changed files
composer types:check             # phpstan
php artisan test --compact       # or --filter=SomeTest for a subset
```

CI (`.github/workflows/tests.yml`, `lint.yml`) runs the same checks against PHP 8.5 and must pass before merging.

## Conventions

- Every change needs a test (Pest, `tests/Feature`). Most tests are feature tests; use `--unit` only for pure logic with no framework dependency.
- Follow existing file conventions before introducing new ones — check a sibling file (e.g. another Livewire component, another Action) before writing a new one.
- Use `php artisan make:*` generators (`--no-interaction`) rather than hand-rolling boilerplate.
- Don't change dependencies or the base directory structure without discussing it first.
- Keep PHPDoc over inline comments; only comment on the non-obvious *why*, not the *what*.

## Branches

`develop`/`main`/`master` are the active branches CI runs against. Open PRs against `main` unless told otherwise.
