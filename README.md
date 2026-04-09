# reddit-readonly-php-client
PHP client for Reddit's public JSON API

## Tooling

This project uses CaptainHook to manage Git hooks and enforces Conventional Commits on `commit-msg`.

Install dependencies to install or refresh hooks locally:

```bash
composer install
```

Valid commit examples:

```text
feat: add subreddit listing client
fix(http): handle rate limit headers
docs: document public API usage
```
