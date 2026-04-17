# Reddit Read-Only PHP Client

PHP client for Reddit's public JSON API.

The package is PSR-native:

- PSR-18 for the injected HTTP client
- PSR-17 for request creation
- PSR-16 for optional caching
- Valinor for internal payload mapping and validation

The package currently targets PHP 8.5.

## Installation

Install the library plus concrete PSR implementations for your application:

```bash
composer require amoreno/reddit-readonly-php-client
composer require guzzlehttp/guzzle php-http/guzzle7-adapter nyholm/psr7 symfony/cache
```

Required runtime dependencies provided by this package:

- `psr/http-client`
- `psr/http-message`
- `psr/http-factory`
- `psr/simple-cache`

You still provide the concrete implementations at the application level.

## Quick Start

```php
<?php

use Amoreno\RedditClient\Client\RedditClient;
use Amoreno\RedditClient\Config\RedditClientConfig;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

$transport = new GuzzleAdapter(new GuzzleClient());
$requestFactory = new Psr17Factory();
$cache = new Psr16Cache(new ArrayAdapter());

$reddit = new RedditClient(
    $transport,
    $requestFactory,
    $cache,
    new RedditClientConfig(
        userAgent: 'my-app/1.0.0',
        requestsPerMinute: 60,
        burstSize: 10,
        cacheTtl: 300,
    ),
);

$listing = $reddit->getSubredditPosts('php');

echo $listing->children[0]->title . PHP_EOL;
```

## Cache Example

Caching is optional. Pass `null` for no cache, or inject any PSR-16 implementation.

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

$cache = new Psr16Cache(new FilesystemAdapter());

$reddit = new RedditClient(
    $transport,
    $requestFactory,
    $cache,
    new RedditClientConfig(
        userAgent: 'my-app/1.0.0',
        cacheTtl: 600,
        cacheKeyPrefix: 'reddit-client',
    ),
);
```

## Public API

### Subreddits

- `getSubredditDetails(string $name)`
- `getSubredditPosts(string $name, ?PaginationOptions $options = null)`
- `getHotSubredditPosts(string $name, ?PaginationOptions $options = null)`
- `getNewSubredditPosts(string $name, ?PaginationOptions $options = null)`
- `getTopSubredditPosts(string $name, TopTimeRange $timeRange = TopTimeRange::Day, ?PaginationOptions $options = null)`
- `getRisingSubredditPosts(string $name, ?PaginationOptions $options = null)`
- `getPopular(SortType $sort = SortType::Hot, ?PaginationOptions $options = null)`
- `getAll(SortType $sort = SortType::Hot, ?PaginationOptions $options = null)`
- `getMultireddit(string $user, string $multiName, ?PaginationOptions $options = null)`

### Posts And Comments

- `getPost(string $subreddit, string $postId, ?string $title = null)`
- `getComments(string $subreddit, string $postId, ?string $title = null, ?CommentOptions $options = null)`

### Users

- `getUserOverview(string $username, ?PaginationOptions $options = null)`
- `getUserSubmitted(string $username, ?PaginationOptions $options = null)`
- `getUserComments(string $username, ?PaginationOptions $options = null)`
- `getUserProfile(string $username)`

### Search

- `search(string $query, ?SearchOptions $options = null)`
- `searchSubreddit(string $subreddit, string $query, ?SearchOptions $options = null)`

## Behavior

- no Reddit authentication required
- deterministic cache keys per endpoint and query params
- response validation before data is returned
- predictable exception types for API, network, cache, validation, and rate-limit failures
- optional live integration tests against Reddit

## Development

```bash
composer install
composer test
composer test:integration
composer analyse
composer format:check
composer refactor:check
composer validate --strict
composer check
```

Live integration tests are opt-in and skipped by default. Enable them with:

```bash
REDDIT_LIVE_TESTS=1 composer test:integration
```

CaptainHook validates Conventional Commits on `commit-msg` and runs `composer check` on `pre-push`.
