# 04 Rate Limiter And Cache Layer

## Goal

Implement the reusable infrastructure around requests: token-bucket rate limiting and optional cache integration.

## Dependency Notes

This ticket depends on the shared config/exception types from ticket 02.

## PHP Refresher

- PSR-16 `CacheInterface` is simple and a good fit here.
- Keep cache failures non-fatal so the client can still fetch live data.

## Tasks

1. Port the Node token-bucket limiter into `src/RateLimit/`.
2. Implement:
   - requests per minute
   - burst size
   - wait for token
   - remaining token inspection
3. Build a cache wrapper in `src/Cache/` around `Psr\SimpleCache\CacheInterface`.
4. Support:
   - enabled/disabled cache
   - TTL
   - key prefixes
   - get / set / delete / clear
5. Swallow cache backend failures and convert them to logs or optional `CacheError` behavior as appropriate.

## Deliverables

- rate limiter class
- cache wrapper class
- unit tests for both

## Done When

- repeated calls can hit cache
- requests can be throttled before transport is used
