# 05 Response Models And Validation

## Goal

Define the shapes of Reddit responses and validate them before the public client returns data.

## Why This Is A Separate Ticket

This is the largest modeling task in the whole project. Keep it isolated before mixing it into endpoint logic.

## PHP Refresher

- DTOs make PHP code easier to navigate than raw nested arrays.
- Arrays are still fine at the transport boundary. The important part is that they stop there and get mapped into DTOs through one internal validation layer.
- Valinor should be treated as an internal implementation detail, not something exposed in the public API.

## Port Order

1. common/base listing models
2. post models
3. comment models
4. subreddit models
5. user models
6. mixed search result models

## Special Cases

- recursive comment replies
- `more` comment nodes
- post + comments tuple response from Reddit comment pages
- optional fields Reddit omits unpredictably

## Tasks

1. Create schema/DTO classes under `src/Schema/` or `src/Dto/`.
2. Build one reusable internal mapper service around Valinor.
3. Build validation/normalization code for each shape on top of that mapper service.
4. Add tests for:
   - minimal valid payloads
   - mixed listing payloads
   - invalid kind values
   - missing required fields

## Suggested Commit

`feat: add response models and validation mapping`

## Done When

- invalid Reddit payloads fail fast
- valid payloads can be consumed safely by the client
