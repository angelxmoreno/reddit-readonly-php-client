<?php

declare(strict_types=1);

use Amoreno\RedditClient\Client\RedditClient;
use Amoreno\RedditClient\Config\CommentOptions;
use Amoreno\RedditClient\Config\PaginationOptions;
use Amoreno\RedditClient\Config\RedditClientConfig;
use Amoreno\RedditClient\Enum\CommentSort;
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

it('fetches subreddit details from the about endpoint', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalSubredditPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $subreddit = $client->getSubredditDetails('r/php');

    expect($subreddit->displayName)->toBe('php')
        ->and((string) $httpClient->lastRequest?->getUri())->toBe('https://www.reddit.com/r/php/about.json');
});

it('encodes reserved characters in subreddit detail path segments', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalSubredditPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $client->getSubredditDetails('space test');

    expect((string) $httpClient->lastRequest?->getUri())
        ->toBe('https://www.reddit.com/r/space%20test/about.json');
});

it('fetches a post from the comments page tuple response', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalPostWithCommentsPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $post = $client->getPost('php', 'post-1', 'example-post');

    expect($post->title)->toBe('Example post')
        ->and((string) $httpClient->lastRequest?->getUri())
            ->toBe('https://www.reddit.com/r/php/comments/post-1/example-post.json');
});

it('fetches comments with deterministic query params', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalPostWithCommentsPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $comments = $client->getComments(
        'php',
        'post-1',
        'example-post',
        new CommentOptions(sort: CommentSort::Top, limit: 10, depth: 3),
    );

    expect($comments->children)->toHaveCount(1)
        ->and((string) $httpClient->lastRequest?->getUri())
            ->toBe('https://www.reddit.com/r/php/comments/post-1/example-post.json?limit=10&depth=3&sort=top');
});

it('fetches user overview listings', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalUserContentListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $listing = $client->getUserOverview('u/angel');

    expect($listing->children)->toHaveCount(2)
        ->and((string) $httpClient->lastRequest?->getUri())
            ->toBe('https://www.reddit.com/user/angel/overview.json?limit=25');
});

it('fetches user submitted listings', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalUserContentListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $client->getUserSubmitted('angel');

    expect((string) $httpClient->lastRequest?->getUri())
        ->toBe('https://www.reddit.com/user/angel/submitted.json?limit=25');
});

it('fetches user comment listings', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalUserContentListingPayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $client->getUserComments('angel');

    expect((string) $httpClient->lastRequest?->getUri())
        ->toBe('https://www.reddit.com/user/angel/comments.json?limit=25');
});

it('fetches a user profile from the about endpoint', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalUserProfilePayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $profile = $client->getUserProfile('u/angel');

    expect($profile->name)->toBe('angel')
        ->and((string) $httpClient->lastRequest?->getUri())
            ->toBe('https://www.reddit.com/user/angel/about.json');
});

it('encodes reserved characters in user endpoint path segments', function (): void {
    $httpClient = new RecordingHttpClient(
        new Response(200, ['Content-Type' => 'application/json'], json_encode(clientMinimalUserProfilePayload(), JSON_THROW_ON_ERROR)),
    );
    $client = new RedditClient(
        $httpClient,
        new Psr17Factory(),
        null,
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    $client->getUserProfile('u/angel test');

    expect((string) $httpClient->lastRequest?->getUri())
        ->toBe('https://www.reddit.com/user/angel%20test/about.json');
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

/**
 * @return array<string, mixed>
 */
function clientMinimalCommentThingPayload(): array
{
    return [
        'kind' => 't1',
        'data' => [
            'subreddit' => 'php',
            'subreddit_name_prefixed' => 'r/php',
            'subreddit_id' => 't5_2qh33',
            'id' => 'comment-1',
            'name' => 't1_comment-1',
            'author' => 'angel',
            'body' => 'Nice post',
            'body_html' => '&lt;div&gt;Nice post&lt;/div&gt;',
            'parent_id' => 't3_post-1',
            'link_id' => 't3_post-1',
            'permalink' => '/r/php/comments/post-1/example-post/comment-1/',
            'created' => 1_700_000_010,
            'created_utc' => 1_700_000_010,
            'score' => 7,
            'depth' => 0,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function clientMinimalCommentListingPayload(): array
{
    return [
        'kind' => 'Listing',
        'data' => [
            'modhash' => null,
            'dist' => 1,
            'children' => [clientMinimalCommentThingPayload()],
            'after' => null,
            'before' => null,
        ],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function clientMinimalPostWithCommentsPayload(): array
{
    return [
        clientMinimalPostListingPayload(),
        clientMinimalCommentListingPayload(),
    ];
}

/**
 * @return array<string, mixed>
 */
function clientMinimalSubredditPayload(): array
{
    return [
        'kind' => 't5',
        'data' => [
            'id' => '2qh33',
            'name' => 't5_2qh33',
            'display_name' => 'php',
            'display_name_prefixed' => 'r/php',
            'title' => 'PHP',
            'description' => 'PHP discussion',
            'public_description' => 'PHP discussion',
            'url' => '/r/php/',
            'subscribers' => 123_456,
            'created' => 1_600_000_000,
            'created_utc' => 1_600_000_000,
            'over18' => false,
            'quarantine' => false,
            'subreddit_type' => 'public',
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function clientMinimalUserProfileSubredditPayload(): array
{
    return [
        'display_name' => 'u_angel',
        'display_name_prefixed' => 'u/angel',
        'title' => 'angel',
        'name' => 't5_profile',
        'url' => '/user/angel/',
        'description' => 'User profile',
        'public_description' => 'User profile',
        'subscribers' => 10,
        'restrict_posting' => true,
        'free_form_reports' => false,
        'show_media' => true,
        'quarantine' => false,
        'accept_followers' => true,
        'link_flair_enabled' => false,
        'disable_contributor_requests' => false,
        'restrict_commenting' => false,
        'subreddit_type' => 'public',
        'over_18' => false,
    ];
}

/**
 * @return array<string, mixed>
 */
function clientMinimalUserProfilePayload(): array
{
    return [
        'kind' => 't2',
        'data' => [
            'id' => 'user-1',
            'name' => 'angel',
            'created' => 1_600_000_000,
            'created_utc' => 1_600_000_000,
            'icon_img' => 'https://example.com/avatar.png',
            'is_employee' => false,
            'is_friend' => false,
            'is_gold' => false,
            'is_mod' => false,
            'verified' => true,
            'hide_from_robots' => false,
            'link_karma' => 100,
            'is_blocked' => false,
            'has_subscribed' => true,
            'subreddit' => clientMinimalUserProfileSubredditPayload(),
            'comment_karma' => 50,
            'total_karma' => 150,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function clientMinimalUserContentListingPayload(): array
{
    return [
        'kind' => 'Listing',
        'data' => [
            'modhash' => null,
            'dist' => 2,
            'children' => [
                clientMinimalPostThingPayload(),
                clientMinimalCommentThingPayload(),
            ],
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
