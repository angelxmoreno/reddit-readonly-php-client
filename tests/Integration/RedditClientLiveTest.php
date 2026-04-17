<?php

declare(strict_types=1);

use Amoreno\RedditClient\Client\RedditClient;
use Amoreno\RedditClient\Config\RedditClientConfig;
use Amoreno\RedditClient\Config\SearchOptions;
use Amoreno\RedditClient\Dto\Post\Post;
use Amoreno\RedditClient\Enum\TopTimeRange;
use Nyholm\Psr7\Factory\Psr17Factory;
use Tests\Support\StreamHttpClient;

function liveClient(): RedditClient
{
    return new RedditClient(
        new StreamHttpClient(),
        new Psr17Factory(),
        null,
        new RedditClientConfig(
            userAgent: 'amoreno-reddit-readonly-php-client-integration-tests/1.0',
            requestsPerMinute: 30,
            burstSize: 5,
        ),
    );
}

function liveTestsEnabled(): bool
{
    return getenv('REDDIT_LIVE_TESTS') === '1';
}

it('fetches a live subreddit listing', function (): void {
    $listing = liveClient()->getSubredditPosts('php');

    expect($listing->children)->not->toBeEmpty()
        ->and($listing->children[0])->toBeInstanceOf(Post::class);
})->skip(!liveTestsEnabled(), 'Set REDDIT_LIVE_TESTS=1 to run live Reddit integration tests.');

it('fetches a live user profile', function (): void {
    $profile = liveClient()->getUserProfile('spez');

    expect($profile->name)->toBe('spez');
})->skip(!liveTestsEnabled(), 'Set REDDIT_LIVE_TESTS=1 to run live Reddit integration tests.');

it('fetches live search results with supported kinds', function (): void {
    $results = liveClient()->search('php', new SearchOptions(
        timeRange: TopTimeRange::Week,
    ));

    expect($results->children)->not->toBeEmpty();
})->skip(!liveTestsEnabled(), 'Set REDDIT_LIVE_TESTS=1 to run live Reddit integration tests.');

it('fetches live comments for a current subreddit post', function (): void {
    $client = liveClient();
    $listing = $client->getHotSubredditPosts('php');

    expect($listing->children)->not->toBeEmpty();

    $post = $listing->children[0];
    $comments = $client->getComments('php', $post->id);

    expect($comments->children)->toBeArray();
})->skip(!liveTestsEnabled(), 'Set REDDIT_LIVE_TESTS=1 to run live Reddit integration tests.');
