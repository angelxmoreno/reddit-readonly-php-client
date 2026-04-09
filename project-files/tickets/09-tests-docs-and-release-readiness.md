# 09 Tests Docs And Release Readiness

## Goal

Finish the package so it is understandable, testable, and ready to publish.

## Tasks

1. Add opt-in integration tests against live Reddit endpoints.
   Gate them behind an environment variable so CI stays deterministic.

2. Update `README.md` with:
   - installation
   - required PSR dependencies
   - quick-start example
   - cache example
   - endpoint overview

3. Compare the PHP client against the Node implementation and check for missing public methods.

4. Run the full quality gate:
   - `composer validate --strict`
   - `composer check`

5. Push and confirm GitHub Actions passes.

## Suggested Integration Coverage

- live subreddit listing
- live user profile
- live search returning subreddit/user kinds
- live comment retrieval

## Done When

- local quality checks pass
- CI passes
- docs reflect the actual PHP API
- you are comfortable tagging a first usable release
