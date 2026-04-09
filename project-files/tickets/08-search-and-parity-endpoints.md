# 08 Search And Parity Endpoints

## Goal

Finish the remaining public API so the PHP package reaches parity with the Node client.

## Methods To Implement

- `search`
- `searchSubreddit`
- `getPopular`
- `getAll`
- `getMultireddit`

## Tasks

1. Implement global search query building.
2. Implement subreddit-scoped search.
3. Support mixed search result types:
   - posts
   - comments
   - subreddits
   - users
4. Implement convenience endpoints for:
   - `r/popular`
   - `r/all`
   - multireddits
5. Add tests for each endpoint and key query variations.

## Done When

- the PHP client exposes the same public endpoint surface as the Node version
- search result validation handles mixed item kinds
