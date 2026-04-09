# PHP Implementation Plan

## Goal

Bring this PHP package to feature parity with the Node reference implementation:

- public Reddit JSON client
- optional caching
- in-process rate limiting
- response validation
- endpoint coverage for subreddits, posts/comments, users, search, `r/all`, `r/popular`, and multireddits
- unit and integration test coverage

This repo already has the tooling foundation in place:

- Pest
- PHPStan
- PHP CS Fixer
- Rector
- CaptainHook
- GitHub Actions CI

The remaining work should be done in the order below.

## Ordered Steps

1. Define the package runtime contract.

   Decide the minimum supported PHP version and add it to `composer.json`.

   In PHP, do not build your own general-purpose HTTP client or cache backend. Standardize the library around PHP-FIG interfaces instead.

   Add the runtime dependencies you want to support before writing real code. A practical baseline is:

   - `psr/http-client` for PSR-18
   - `psr/http-message` for PSR-7 message types
   - `psr/http-factory` for PSR-17 request creation
   - `psr/simple-cache` or `psr/cache` for cache interoperability
   - your chosen response validation or DTO-mapping library

   The package should accept user-provided implementations of these interfaces rather than shipping its own HTTP transport or cache store.

2. Create the production namespace layout under `src/`.

   Mirror the Node structure closely so parity stays obvious during the port:

   - `src/Client/`
   - `src/Cache/`
   - `src/RateLimit/`
   - `src/Http/`
   - `src/Schema/` or `src/Dto/`
   - `src/Exception/`

   Keep the file layout intentionally parallel to the Node repo:

   - `client/reddit-client.ts`
   - `cache/cache-layer.ts`
   - `rate-limit/rate-limiter.ts`
   - `utils/http-client.ts`
   - `schemas/*.ts`

3. Port the shared configuration and exception types first.

   Before implementing behavior, define the PHP equivalents of the Node config and error model:

   - `RedditClientConfig`
   - `PaginationOptions`
   - `CommentOptions`
   - `SearchOptions`
   - `SortType`
   - `TopTimeRange`
   - `RedditApiError`
   - `NetworkError`
   - `ValidationError`
   - `RateLimitError`
   - `CacheError`

   This gives every later class a stable contract.

4. Implement a thin Reddit transport layer over PSR-18.

   Do not port the Node `HttpClient` as a standalone general-purpose client. Instead, write a small internal transport service that depends on:

   - `Psr\Http\Client\ClientInterface`
   - `Psr\Http\Message\RequestFactoryInterface`

   If you need stream creation or request mutation helpers, add the appropriate PSR-17 factory interfaces too.

   Required behavior:

   - build GET requests for Reddit endpoints
   - add the configured user agent header
   - JSON-only responses
   - throw on non-2xx responses
   - return decoded arrays
   - normalize transport failures into your network exception

   One important difference from the Node version: timeout is not standardized by PSR-18, so that concern should typically be owned by the concrete client the user injects.

5. Implement the rate limiter.

   Port the token-bucket logic from `src/rate-limit/rate-limiter.ts`.

   Required behavior:

   - `requestsPerMinute`
   - `burstSize`
   - blocking wait for the next token
   - methods to inspect remaining tokens and time until next token

   Keep it process-local like the Node version. Do not over-design distributed rate limiting yet.

6. Implement the cache integration layer.

   Port the Node cache behavior as a thin wrapper around a user-supplied PSR cache implementation, not as a cache backend of your own.

   Required behavior:

   - enable/disable cache without changing calling code
   - cache key prefixing
   - TTL support
   - non-fatal cache failures
   - `get`, `set`, `delete`, `clear`, `isEnabled`

   Prefer `Psr\SimpleCache\CacheInterface` unless you have a strong reason to use PSR-6.

7. Define the Reddit response models and validation strategy.

   This is the largest porting task. The Node project validates:

   - common Reddit listing/base structures
   - posts
   - comments, including recursive replies and `more` nodes
   - subreddit info
   - user profile and user search results
   - mixed search result listings

   Port these in this order:

   - common/base listing structures
   - post models
   - comment models
   - subreddit models
   - user/search models

   Completion criteria for this step:

   - invalid payloads fail fast
   - valid payloads are normalized into predictable PHP arrays or DTOs
   - recursive comment replies are supported
   - the post-plus-comments tuple shape is handled cleanly

8. Implement the main `RedditClient`.

   Once the primitives exist, port the main client class and keep the public API close to the Node version.

   Implement these methods in order:

   1. `getSubreddit`
   2. `getSubredditHot`
   3. `getSubredditNew`
   4. `getSubredditTop`
   5. `getSubredditRising`
   6. `getPost`
   7. `getComments`
   8. `getUserOverview`
   9. `getUserSubmitted`
   10. `getUserComments`
   11. `getUserProfile`
   12. `search`
   13. `searchSubreddit`
   14. `getPopular`
   15. `getAll`
   16. `getMultireddit`

   Also port the internal helpers:

   - subreddit-with-sort helper
   - user-content helper
   - pagination param helper
   - fetch/cache/validate helper

9. Keep cache keys and URL building deterministic.

   The Node implementation derives cache keys directly from endpoint intent plus query params. Preserve that pattern in PHP.

   For every endpoint:

   - build query parameters in a consistent order
   - build the final request URL from those params
   - derive a stable cache key from the same values

   Do not postpone this detail. If you get it wrong early, tests and cache behavior will drift.

10. Port the unit tests module by module.

   Use the Node tests as the checklist for PHP coverage. Write PHP tests in the same order as the implementation:

   - HTTP client tests
   - rate limiter tests
   - cache layer tests
   - schema/validation tests
   - Reddit client tests with mocked HTTP responses

   Focus first on behavior parity, not on matching the exact Node fixture volume.

11. Add integration tests against live Reddit endpoints.

   Port the Node integration intent, but keep them opt-in so CI is stable.

   Suggested integration coverage:

   - live subreddit listing
   - live user profile
   - live search with subreddit/user result types
   - live comments fetch on a known post

   Gate them behind an environment variable so the default CI workflow stays deterministic.

12. Write the public package docs after the API is stable.

   Update `README.md` only after the client surface is real.

   Include:

   - installation
   - quick start
   - configuration
   - caching example
   - method reference
   - testing commands

   Keep the README aligned with the actual PHP API, not the Node syntax.

13. Do a parity audit before release.

   Compare the PHP client against the Node repo one last time and verify:

   - every Node public method exists in PHP
   - config options are equivalent
   - error classes are equivalent enough for users
   - caching and rate limiting are documented
   - test and quality commands pass
   - CI passes from a clean checkout

## Recommended Delivery Sequence

If you want the shortest path to a usable package, split the work into these milestones:

1. Core infrastructure

   PSR-18 transport glue, PSR cache glue, rate limiter, config objects, exceptions

2. Subreddit-only MVP

   `getSubreddit`, `getSubredditHot`, `getSubredditNew`, `getSubredditTop`, `getSubredditRising`

3. Posts and comments

   `getPost`, `getComments`, recursive comment validation

4. User and search support

   user endpoints, global search, subreddit search, mixed result validation

5. Final parity

   `getPopular`, `getAll`, `getMultireddit`, integration tests, docs, release prep

## Suggested Acceptance Checklist

The project is meaningfully complete when all of the following are true:

- `composer check` passes
- CI passes on GitHub
- the client can fetch live Reddit JSON successfully
- invalid payloads raise validation errors
- cache can be enabled, disabled, and swapped
- rate limiting is applied before outbound requests
- all Node public endpoints have PHP equivalents
- README examples execute against the real PHP API

## Reference

Primary reference implementation:

- Node repo: https://github.com/angelxmoreno/reddit-readonly-client
