# Repository Guidelines

## Project Structure & Module Organization

This package is a PHP client for Reddit’s public JSON API. Production code lives in `src/` under the `Amoreno\RedditClient\` namespace. Tests live in `tests/`, with Pest bootstrapping in `tests/Pest.php` and the shared base class in `tests/TestCase.php`. CI is defined in `.github/workflows/ci.yml`. Tooling and planning files are kept at the repo root (`captainhook.json`, `phpstan.neon.dist`, `rector.php`, `.php-cs-fixer.dist.php`) and in `project-files/` for implementation notes.

## Build, Test, and Development Commands

- `composer install`: install dependencies and refresh Git hooks.
- `composer test`: run the Pest suite.
- `./vendor/bin/pest tests/Unit/ExampleTest.php`: run a single test file.
- `composer analyse`: run PHPStan at max level on `src/` and `tests/`.
- `composer format`: apply PHP CS Fixer rules.
- `composer format:check`: check formatting without writing changes.
- `composer refactor`: run Rector transforms.
- `composer refactor:check`: dry-run Rector.
- `composer check`: run tests, static analysis, and formatting checks; this is the local pre-push gate and the CI command.

## Coding Style & Naming Conventions

Follow PSR-12 formatting with four-space indentation. PHP CS Fixer enforces short arrays, single quotes, no unused imports, and trailing commas in multiline arrays. Rector is configured for PHP 8.5 and applies code-quality, dead-code, and coding-style sets. Keep source files under `src/` namespaced as `Amoreno\RedditClient\...`; test classes stay under `Tests\...`.

## Testing Guidelines

Pest is the test framework, configured through `phpunit.xml` and `tests/Pest.php`. Place new tests in `tests/Feature` or `tests/Unit` and use the `*Test.php` suffix so PHPUnit discovery works. `composer check` should pass before pushing; CaptainHook runs it automatically on `pre-push`.

## Commit & Pull Request Guidelines

Commits follow Conventional Commits and are enforced by CaptainHook on `commit-msg`. Recent history shows patterns like `feat: renamed namespace`, `chore: add php quality tooling`, and `ci: added ci tests`. Keep the first line lowercase and in `type: description` form; optional bodies should follow a blank line.

Do not commit work before the user reviews it and explicitly asks for a commit. The expected flow in this repo is:

- make the code or documentation changes
- run the relevant checks
- present the diff or affected files for review
- commit only after the user approves

For pull requests, ensure CI passes on the branch head and describe any runtime, namespace, or tooling changes clearly.
