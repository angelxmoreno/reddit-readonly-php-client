# 03 PSR-18 Transport Layer

## Goal

Build the low-level Reddit transport that wraps an injected PSR-18 client and PSR-17 request factory.

## Why This Comes Before Endpoint Work

The main client should not know how to create requests, attach headers, or decode JSON.

## PHP Refresher

- PSR-18 sends a PSR-7 request and returns a PSR-7 response.
- Your code should depend on interfaces, not concrete HTTP clients.
- Timeouts are not standardized by PSR-18, so let consumers configure them in their chosen client.

## Tasks

1. Create a transport class under `src/Http/`.
2. Inject:
   - `ClientInterface`
   - `RequestFactoryInterface`
3. Implement GET request handling for Reddit JSON endpoints.
4. Always send a user-agent header.
5. Reject non-2xx responses with `RedditApiError`.
6. Reject non-JSON responses with `ValidationError` or a transport-specific exception.
7. Decode JSON into arrays.
8. Wrap transport-level failures as `NetworkError`.

## Deliverables

- transport class
- focused unit tests for request creation, status handling, and JSON decoding

## Done When

- you can hand the transport a URL and get validated decoded JSON or a predictable exception
