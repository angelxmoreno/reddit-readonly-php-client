# 02 Source Layout And Core Types

## Goal

Create the base source tree and the shared configuration / exception types that all later code will use.

## Why This Comes Next

Transport, caching, validation, and the client all need stable contracts.

## PHP Refresher

- Use one class per file.
- Namespace should mirror the folder path.
- Prefer small immutable value objects for options instead of passing giant arrays everywhere.

## Suggested Structure

- `src/Client/`
- `src/Http/`
- `src/RateLimit/`
- `src/Cache/`
- `src/Exception/`
- `src/Value/` or `src/Config/`

## Tasks

1. Replace the placeholder `src/Client.php` with real folders/classes.
2. Create value/config objects for:
   - client config
   - pagination options
   - comment options
   - search options
3. Create enums or constants for sort and time-range values.
4. Create exceptions:
   - `RedditApiError`
   - `NetworkError`
   - `ValidationError`
   - `RateLimitError`
   - `CacheError`

## Deliverables

- source folders created
- config/value classes committed
- exception hierarchy committed

## Suggested Commit

`feat: add core client types and exception hierarchy`

## Done When

- autoloading works for all new classes
- PHPStan sees the new namespace structure cleanly
