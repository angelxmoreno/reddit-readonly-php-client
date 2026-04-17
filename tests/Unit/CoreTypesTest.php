<?php

declare(strict_types=1);

use Amoreno\RedditClient\Client\RedditClient;
use Amoreno\RedditClient\Config\CommentOptions;
use Amoreno\RedditClient\Config\PaginationOptions;
use Amoreno\RedditClient\Config\RedditClientConfig;
use Amoreno\RedditClient\Config\SearchOptions;
use Amoreno\RedditClient\Enum\CommentSort;
use Amoreno\RedditClient\Enum\SearchSort;
use Amoreno\RedditClient\Enum\SortType;
use Amoreno\RedditClient\Enum\TopTimeRange;
use Amoreno\RedditClient\Exception\CacheError;
use Amoreno\RedditClient\Exception\NetworkError;
use Amoreno\RedditClient\Exception\RateLimitError;
use Amoreno\RedditClient\Exception\RedditApiError;
use Amoreno\RedditClient\Exception\ValidationError;

it('autoloads the public client entry point', function (): void {
    expect(class_exists(RedditClient::class))->toBeTrue();
});

it('provides default client config values', function (): void {
    $config = new RedditClientConfig();

    expect($config->baseUri)->toBe('https://www.reddit.com')
        ->and($config->userAgent)->toBe('amoreno/reddit-readonly-php-client')
        ->and($config->requestsPerMinute)->toBe(60)
        ->and($config->burstSize)->toBe(10)
        ->and($config->cacheTtl)->toBe(300)
        ->and($config->cacheKeyPrefix)->toBe('reddit-readonly-client');
});

it('provides stable option object defaults', function (): void {
    $pagination = new PaginationOptions();
    $comments = new CommentOptions();
    $search = new SearchOptions();

    expect($pagination->limit)->toBe(25)
        ->and($comments->sort)->toBe(CommentSort::Confidence)
        ->and($search->sort)->toBe(SearchSort::Relevance)
        ->and($search->timeRange)->toBe(TopTimeRange::All);
});

it('defines the expected enum values', function (): void {
    expect(SortType::Hot->value)->toBe('hot')
        ->and(TopTimeRange::Month->value)->toBe('month')
        ->and(CommentSort::Qa->value)->toBe('qa')
        ->and(SearchSort::Comments->value)->toBe('comments');
});

it('autoloads the exception hierarchy', function (): void {
    expect(new RedditApiError('api'))->toBeInstanceOf(RedditApiError::class)
        ->and(new NetworkError('network'))->toBeInstanceOf(NetworkError::class)
        ->and(new ValidationError('validation'))->toBeInstanceOf(ValidationError::class)
        ->and(new RateLimitError('rate-limit'))->toBeInstanceOf(RateLimitError::class)
        ->and(new CacheError('cache'))->toBeInstanceOf(CacheError::class);
});

it('exposes the expected public client methods', function (): void {
    $methods = array_values(array_filter(
        get_class_methods(RedditClient::class),
        static fn (string $method): bool => $method !== '__construct',
    ));
    sort($methods);

    expect($methods)->toBe([
        'getAll',
        'getComments',
        'getHotSubredditPosts',
        'getMultireddit',
        'getNewSubredditPosts',
        'getPopular',
        'getPost',
        'getRisingSubredditPosts',
        'getSubredditDetails',
        'getSubredditPosts',
        'getTopSubredditPosts',
        'getUserComments',
        'getUserOverview',
        'getUserProfile',
        'getUserSubmitted',
        'search',
        'searchSubreddit',
    ]);
});
