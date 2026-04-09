# reddit-readonly-php-client
PHP client for Reddit's public JSON API

## Tooling

This project uses CaptainHook to manage Git hooks and enforces Conventional Commits on `commit-msg`.

Install dependencies to install or refresh hooks locally:

```bash
composer install
```

Common tooling commands:

```bash
composer test
composer analyse
composer format
composer format:check
composer refactor
composer refactor:check
composer check
```

`composer check` runs the test suite, PHPStan, and PHP CS Fixer in dry-run mode. CaptainHook runs this check on `pre-push`.

Valid commit examples:

```text
feat: add subreddit listing client
fix(http): handle rate limit headers
docs: document public API usage
```
