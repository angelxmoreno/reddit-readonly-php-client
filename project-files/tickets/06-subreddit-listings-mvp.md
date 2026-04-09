# 06 Subreddit Listings MVP

## Goal

Ship the first useful slice of the client: subreddit listing endpoints.

## Why Start Here

These are the simplest endpoints and let you prove transport, validation, cache, and rate limiting together.

## Methods To Implement

- `getSubreddit`
- `getSubredditHot`
- `getSubredditNew`
- `getSubredditTop`
- `getSubredditRising`

## Tasks

1. Create the main client class under `src/Client/`.
2. Inject transport, rate limiter, and optional cache.
3. Add default config values such as user agent and default rate limits.
4. Build deterministic query strings for pagination.
5. Build stable cache keys from endpoint + params.
6. Validate the response before returning it.
7. Write unit tests with mocked HTTP responses.

## Done When

- you can fetch a subreddit listing
- pagination works
- repeated calls can hit cache
- all MVP methods are covered by tests
