# Reddit Read-Only PHP Client

PHP client for Reddit's public JSON API.

This package is still under active development. The examples below describe the intended final API so implementation work can be measured against a clear target.

The package currently targets PHP 8.5.

## Design

The library is meant to be PHP-native and PSR-based:

- PSR-18 for the injected HTTP client
- PSR-17 for creating requests
- PSR-16 for optional caching
- Valinor for internal payload-to-DTO mapping and validation

The library itself is responsible for:

- building Reddit requests
- validating Reddit JSON responses
- applying optional caching
- applying in-process rate limiting
- exposing a clean `Amoreno\RedditClient\...` API

This package will depend on the PSR interfaces and Valinor directly. Consumers are still expected to provide concrete implementations for:

- a PSR-18 transport
- a PSR-17 request factory
- optionally, a PSR-16 cache implementation

## Intended Installation

```bash
composer require amoreno/reddit-readonly-php-client

# Plus your preferred implementations
composer require guzzlehttp/guzzle php-http/guzzle7-adapter nyholm/psr7 symfony/cache
```

## Intended Quick Start

```php
<?php

use Amoreno\RedditClient\Client\RedditClient;
use Amoreno\RedditClient\Config\RedditClientConfig;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

$transport = new GuzzleAdapter(new GuzzleClient());
$requestFactory = new Psr17Factory();
$cache = new Psr16Cache(new ArrayAdapter());

$redditClient = new RedditClient(
    $transport,
    $requestFactory,
    $cache,
    new RedditClientConfig(
        userAgent: 'my-app/1.0.0',
        requestsPerMinute: 60,
        burstSize: 10,
        cacheTtl: 300,
    )
);

$listing = $redditClient->getSubredditPosts('php');
echo $listing->children[0]->title . PHP_EOL;
```

## Intended Public API

### Subreddits

- `getSubredditDetails(string $name)`
- `getSubredditPosts(string $name, PaginationOptions $options = null)`
- `getHotSubredditPosts(string $name, PaginationOptions $options = null)`
- `getNewSubredditPosts(string $name, PaginationOptions $options = null)`
- `getTopSubredditPosts(string $name, TopTimeRange $timeRange = TopTimeRange::Day, PaginationOptions $options = null)`
- `getRisingSubredditPosts(string $name, PaginationOptions $options = null)`
- `getPopular(SortType $sort = SortType::Hot, PaginationOptions $options = null)`
- `getAll(SortType $sort = SortType::Hot, PaginationOptions $options = null)`
- `getMultireddit(string $user, string $multiName, PaginationOptions $options = null)`

### Posts And Comments

- `getPost(string $subreddit, string $postId, ?string $title = null)`
- `getComments(string $subreddit, string $postId, ?string $title = null, ?CommentOptions $options = null)`

### Users

- `getUserOverview(string $username, PaginationOptions $options = null)`
- `getUserSubmitted(string $username, PaginationOptions $options = null)`
- `getUserComments(string $username, PaginationOptions $options = null)`
- `getUserProfile(string $username)`

### Search

- `search(string $query, ?SearchOptions $options = null)`
- `searchSubreddit(string $subreddit, string $query, ?SearchOptions $options = null)`

## Intended Behavior

- No Reddit authentication required
- Deterministic cache keys per endpoint and query params
- Response validation before data is returned
- Predictable exceptions for API, network, cache, validation, and rate-limit failures
- Optional integration tests against live Reddit endpoints

## Development

```bash
composer install
composer test
composer analyse
composer format:check
composer refactor:check
composer check
```

CaptainHook validates Conventional Commits on `commit-msg` and runs `composer check` on `pre-push`.
