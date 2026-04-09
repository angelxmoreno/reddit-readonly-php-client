# PHP Implementation Roadmap

## Goal

Build a PHP library that reaches practical feature parity with the Node reference implementation:

- fetch Reddit's public JSON endpoints
- validate responses
- support optional caching
- apply in-process rate limiting
- expose a clean `Amoreno\RedditClient\...` API
- ship with tests, quality checks, and CI

## Design Direction

This project should be PHP-native, not a line-by-line rewrite of the Node version.

- Use PSR interfaces for integrations:
  - `psr/http-client` for PSR-18 HTTP clients
  - `psr/http-message` for PSR-7 messages
  - `psr/http-factory` for PSR-17 request factories
  - `psr/simple-cache` for optional cache support
- Implement Reddit-specific behavior in this package:
  - request building
  - cache key generation
  - rate limiting
  - response validation / mapping
  - exceptions

## Recommended Build Order

Work through the tickets in `project-files/tickets/` in numeric order. The dependencies matter.

1. Runtime contract and package dependencies
2. Source layout, namespaces, and config objects
3. PSR-18 transport layer
4. Rate limiter and cache integration
5. Response models and validation
6. Subreddit listing MVP
7. Post, comment, and user endpoints
8. Search and parity endpoints
9. Tests, docs, and release readiness

## Mapping From Node To PHP

Use the Node repo as the behavioral contract:

- `src/client/reddit-client.ts` -> main PHP client
- `src/client/types.ts` -> config/value objects and exceptions
- `src/utils/http-client.ts` -> thin Reddit transport over PSR-18
- `src/rate-limit/rate-limiter.ts` -> in-memory token bucket
- `src/cache/cache-layer.ts` -> wrapper around PSR-16 cache
- `src/schemas/*.ts` -> PHP DTOs / validators / normalizers

Do not copy TypeScript patterns directly if they make PHP awkward. Preserve behavior, not syntax.

## Practical Guidance

- Keep new code under `src/` and match the `Amoreno\RedditClient\` namespace.
- Keep endpoint work incremental. Get subreddit listings working before touching comments or search.
- Prefer DTOs or clearly defined validated arrays over loose associative arrays everywhere.
- Make tests prove each layer before stacking the next one.
- Keep `composer check` green after each ticket.

## Exit Condition

The implementation is in good shape when:

- all planned client methods exist
- the package can fetch live Reddit JSON through an injected PSR-18 client
- caching and rate limiting are optional but working
- invalid payloads fail predictably
- unit tests and optional integration tests exist
- `composer validate --strict` and `composer check` pass
