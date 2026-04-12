# 07 Detail Posts Comments And User Endpoints

## Goal

Expand the client beyond listing endpoints into detail, post, comment, and user APIs.

## Methods To Implement

- `getSubredditDetails`
- `getPost`
- `getComments`
- `getUserOverview`
- `getUserSubmitted`
- `getUserComments`
- `getUserProfile`

## Dependency Notes

This ticket assumes:

- transport exists
- cache/rate limiting exist
- post/comment/user validation exists

## Tasks

1. Implement subreddit detail URL building for `/r/{subreddit}/about.json`.
2. Implement post/comment URL building.
3. Support optional post slug handling for comment-page URLs.
4. Parse the tuple returned by Reddit comment-page endpoints.
5. Implement shared helper methods for user content endpoints.
6. Add unit tests for:
   - subreddit details
   - comment listing retrieval
   - post-with-comments retrieval
   - user profile
   - user overview/submitted/comments feeds

## Suggested Commit

`feat: add post comment and user endpoints`

## Done When

- all listed methods work with mocked responses
- comment recursion and `more` placeholders are handled correctly
