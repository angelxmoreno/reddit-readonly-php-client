<?php

declare(strict_types=1);

use Amoreno\RedditClient\Client\RedditClient;
use Amoreno\RedditClient\Config\PaginationOptions;
use Amoreno\RedditClient\Config\RedditClientConfig;
use Amoreno\RedditClient\Enum\TopTimeRange;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

it('fetches the default subreddit listing using the hot endpoint', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalPostListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $listing = $client->getSubredditPosts('php');

    expect($listing->children)->toHaveCount(1)
        ->and($listing->children[0]->title)->toBe('Example post')
        ->and($httpClient->requestCount)->toBe(1)
        ->and((string) $httpClient->lastRequest?->getUri())->toBe('https://www.reddit.com/r/php/hot.json?limit=25');
});

it('builds deterministic query strings for paginated subreddit listings', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalPostListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $client->getNewSubredditPosts('r/php', new PaginationOptions(after: 't3_abc123', limit: 10, count: 10));

    expect((string) $httpClient->lastRequest?->getUri())
        ->toBe('https://www.reddit.com/r/php/new.json?after=t3_abc123&count=10&limit=10');
});

it('adds the time range when fetching top subreddit listings', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalPostListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $client->getTopSubredditPosts('php', TopTimeRange::Week, new PaginationOptions(limit: 5));

    expect((string) $httpClient->lastRequest?->getUri())
        ->toBe('https://www.reddit.com/r/php/top.json?limit=5&t=week');
});

it('uses cached subreddit listings before hitting the transport', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalPostListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $cache = new InMemoryClientCache();
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        $cache,
        new RedditClientConfig(userAgent: 'test-agent', cacheTtl: 300),
    );

    $first = $client->getRisingSubredditPosts('php');
    $second = $client->getRisingSubredditPosts('php');

    expect($httpClient->requestCount)->toBe(1)
        ->and($cache->lastSetKey)->toBe('reddit-readonly-client:get:r/php/rising.json?limit=25')
        ->and($second)->toBe($first);
});

it('rejects empty subreddit names', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalPostListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    expect(fn (): mixed => $client->getSubredditPosts('   '))
        ->toThrow(InvalidArgumentException::class, 'The subreddit name cannot be empty.');
});

it('encodes reserved characters in subreddit path segments', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalPostListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $client->getSubredditPosts('space test');

    expect((string) $httpClient->lastRequest?->getUri())
        ->toBe('https://www.reddit.com/r/space%20test/hot.json?limit=25');
});

/**
 * @return array<string, mixed>
 */
function clientMinimalPostThingPayload(): array
{
    return [
        'kind' => 't3',
        'data' => [
            'subreddit' => 'php',
            'subreddit_name_prefixed' => 'r/php',
            'subreddit_id' => 't5_2qh33',
            'id' => 'post-1',
            'name' => 't3_post-1',
            'author' => 'angel',
            'permalink' => '/r/php/comments/post-1/example-post/',
            'created' => 1_700_000_000,
            'created_utc' => 1_700_000_000,
            'score' => 42,
            'num_comments' => 3,
            'title' => 'Example post',
            'url' => 'https://www.reddit.com/r/php/comments/post-1/example-post/',
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function clientMinimalPostListingPayload(): array
{
    return [
        'kind' => 'Listing',
        'data' => [
            'modhash' => null,
            'dist' => 1,
            'children' => [clientMinimalPostThingPayload()],
            'after' => null,
            'before' => null,
        ],
    ];
}

final class RecordingHttpClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    public int $requestCount = 0;

    public function __construct(private readonly ResponseInterface $response)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        $this->requestCount++;

        return $this->response;
    }
}

final class InMemoryClientCache implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $items = [];

    public string $lastSetKey = '';

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->lastSetKey = $key;
        $this->items[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get((string) $key, $default);
        }

        return $values;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}
