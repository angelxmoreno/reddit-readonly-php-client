<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Client;

use Amoreno\RedditClient\Cache\CacheLayer;
use Amoreno\RedditClient\Config\CommentOptions;
use Amoreno\RedditClient\Config\PaginationOptions;
use Amoreno\RedditClient\Config\RedditClientConfig;
use Amoreno\RedditClient\Config\SearchOptions;
use Amoreno\RedditClient\Dto\Comment\CommentListing;
use Amoreno\RedditClient\Dto\Post\Post;
use Amoreno\RedditClient\Dto\Post\PostListing;
use Amoreno\RedditClient\Dto\Post\PostWithComments;
use Amoreno\RedditClient\Dto\Subreddit\Subreddit;
use Amoreno\RedditClient\Dto\User\SearchResults;
use Amoreno\RedditClient\Dto\User\UserContentListing;
use Amoreno\RedditClient\Dto\User\UserProfile;
use Amoreno\RedditClient\Enum\SortType;
use Amoreno\RedditClient\Enum\TopTimeRange;
use Amoreno\RedditClient\Http\RedditTransport;
use Amoreno\RedditClient\Mapping\RedditPayloadMapper;
use Amoreno\RedditClient\RateLimit\TokenBucketRateLimiter;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class RedditClient
{
    /**
     * @param ClientInterface $httpClient Concrete PSR-18 transport used to send Reddit requests.
     * @param RequestFactoryInterface $requestFactory PSR-17 factory used to build outbound GET requests.
     * @param CacheInterface|null $cache Optional PSR-16 cache backend for response caching.
     * @param RedditClientConfig|null $config Optional client-wide settings such as base URI, user agent, rate limits, and cache TTL.
     */
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        ?CacheInterface $cache = null,
        ?RedditClientConfig $config = null,
    ) {
        $this->config = $config ?? new RedditClientConfig();
        $this->mapper = new RedditPayloadMapper();
        $this->rateLimiter = new TokenBucketRateLimiter(
            $this->config->requestsPerMinute,
            $this->config->burstSize,
        );
        $this->transport = new RedditTransport($this->httpClient, $this->requestFactory, $this->config);
        $this->cache = new CacheLayer(
            $cache,
            ttl: $this->config->cacheTtl,
            keyPrefix: sprintf('%s:', $this->config->cacheKeyPrefix),
        );
    }

    private RedditClientConfig $config;

    private RedditTransport $transport;

    private RedditPayloadMapper $mapper;

    private TokenBucketRateLimiter $rateLimiter;

    private CacheLayer $cache;

    public function getSubredditPosts(string $name, ?PaginationOptions $options = null): PostListing
    {
        return $this->getHotSubredditPosts($name, $options);
    }

    public function getHotSubredditPosts(string $name, ?PaginationOptions $options = null): PostListing
    {
        return $this->fetchSubredditListing($name, 'hot', $options);
    }

    public function getNewSubredditPosts(string $name, ?PaginationOptions $options = null): PostListing
    {
        return $this->fetchSubredditListing($name, 'new', $options);
    }

    public function getTopSubredditPosts(
        string $name,
        TopTimeRange $timeRange = TopTimeRange::Day,
        ?PaginationOptions $options = null,
    ): PostListing {
        return $this->fetchSubredditListing($name, 'top', $options, $timeRange);
    }

    public function getRisingSubredditPosts(string $name, ?PaginationOptions $options = null): PostListing
    {
        return $this->fetchSubredditListing($name, 'rising', $options);
    }

    public function getSubredditDetails(string $name): Subreddit
    {
        $normalizedSubreddit = $this->normalizeSubredditName($name);
        $encodedSubreddit = rawurlencode($normalizedSubreddit);
        $path = sprintf('/r/%s/about.json', $encodedSubreddit);
        $cacheKey = $this->buildCacheKey($path, []);

        $cached = $this->cache->get($cacheKey);

        if ($cached instanceof Subreddit) {
            return $cached;
        }

        $this->rateLimiter->waitForToken();

        $payload = $this->transport->get($this->buildUrl($path, []));
        $subreddit = $this->mapper->mapSubreddit($this->expectAssociativePayload($payload));

        $this->cache->set($cacheKey, $subreddit);

        return $subreddit;
    }

    public function getPost(string $subreddit, string $postId, ?string $title = null): Post
    {
        return $this->fetchPostWithComments($subreddit, $postId, $title)->post;
    }

    public function getComments(
        string $subreddit,
        string $postId,
        ?string $title = null,
        ?CommentOptions $options = null,
    ): CommentListing {
        return $this->fetchPostWithComments(
            $subreddit,
            $postId,
            $title,
            $this->buildCommentQuery($options),
        )->comments;
    }

    public function getUserOverview(string $username, ?PaginationOptions $options = null): UserContentListing
    {
        return $this->fetchUserContentListing($username, 'overview', $options);
    }

    public function getUserSubmitted(string $username, ?PaginationOptions $options = null): UserContentListing
    {
        return $this->fetchUserContentListing($username, 'submitted', $options);
    }

    public function getUserComments(string $username, ?PaginationOptions $options = null): UserContentListing
    {
        return $this->fetchUserContentListing($username, 'comments', $options);
    }

    public function getUserProfile(string $username): UserProfile
    {
        $normalizedUsername = $this->normalizeUsername($username);
        $encodedUsername = rawurlencode($normalizedUsername);
        $path = sprintf('/user/%s/about.json', $encodedUsername);
        $cacheKey = $this->buildCacheKey($path, []);

        $cached = $this->cache->get($cacheKey);

        if ($cached instanceof UserProfile) {
            return $cached;
        }

        $this->rateLimiter->waitForToken();

        $payload = $this->transport->get($this->buildUrl($path, []));
        $profile = $this->mapper->mapUserProfile($this->expectAssociativePayload($payload));

        $this->cache->set($cacheKey, $profile);

        return $profile;
    }

    public function search(string $query, ?SearchOptions $options = null): SearchResults
    {
        return $this->fetchSearchResults($query, '/search.json', $options);
    }

    public function searchSubreddit(string $subreddit, string $query, ?SearchOptions $options = null): SearchResults
    {
        $normalizedSubreddit = $this->normalizeSubredditName($subreddit);
        $encodedSubreddit = rawurlencode($normalizedSubreddit);

        return $this->fetchSearchResults(
            $query,
            sprintf('/r/%s/search.json', $encodedSubreddit),
            $options,
            ['restrict_sr' => '1'],
        );
    }

    public function getPopular(SortType $sort = SortType::Hot, ?PaginationOptions $options = null): PostListing
    {
        return $this->fetchSubredditListing('popular', $sort->value, $options);
    }

    public function getAll(SortType $sort = SortType::Hot, ?PaginationOptions $options = null): PostListing
    {
        return $this->fetchSubredditListing('all', $sort->value, $options);
    }

    public function getMultireddit(string $user, string $multiName, ?PaginationOptions $options = null): PostListing
    {
        $normalizedUsername = $this->normalizeUsername($user);
        $normalizedMultiName = $this->normalizeMultiredditName($multiName);
        $encodedUsername = rawurlencode($normalizedUsername);
        $encodedMultiName = rawurlencode($normalizedMultiName);
        $query = $this->buildPaginationQuery($options);
        $path = sprintf('/user/%s/m/%s.json', $encodedUsername, $encodedMultiName);
        $cacheKey = $this->buildCacheKey($path, $query);

        $cached = $this->cache->get($cacheKey);

        if ($cached instanceof PostListing) {
            return $cached;
        }

        $this->rateLimiter->waitForToken();

        $payload = $this->transport->get($this->buildUrl($path, $query));
        $listing = $this->mapper->mapPostListing($this->expectAssociativePayload($payload));

        $this->cache->set($cacheKey, $listing);

        return $listing;
    }

    private function fetchSubredditListing(
        string $subreddit,
        string $sort,
        ?PaginationOptions $options = null,
        ?TopTimeRange $timeRange = null,
    ): PostListing {
        $normalizedSubreddit = $this->normalizeSubredditName($subreddit);
        $encodedSubreddit = rawurlencode($normalizedSubreddit);
        $query = $this->buildListingQuery($options, $timeRange);
        $path = sprintf('/r/%s/%s.json', $encodedSubreddit, $sort);
        $cacheKey = $this->buildCacheKey($path, $query);

        $cached = $this->cache->get($cacheKey);

        if ($cached instanceof PostListing) {
            return $cached;
        }

        $this->rateLimiter->waitForToken();

        $payload = $this->transport->get($this->buildUrl($path, $query));
        $listing = $this->mapper->mapPostListing($this->expectAssociativePayload($payload));

        $this->cache->set($cacheKey, $listing);

        return $listing;
    }

    /**
     * @param array<string, scalar> $query
     */
    private function fetchPostWithComments(
        string $subreddit,
        string $postId,
        ?string $title = null,
        array $query = [],
    ): PostWithComments {
        $normalizedSubreddit = $this->normalizeSubredditName($subreddit);
        $normalizedPostId = $this->normalizePostId($postId);
        $encodedSubreddit = rawurlencode($normalizedSubreddit);
        $encodedPostId = rawurlencode($normalizedPostId);
        $path = sprintf('/r/%s/comments/%s', $encodedSubreddit, $encodedPostId);

        if ($title !== null && trim($title) !== '') {
            $path .= '/' . rawurlencode(trim($title, '/'));
        }

        $path .= '.json';

        $cacheKey = $this->buildCacheKey($path, $query);
        $cached = $this->cache->get($cacheKey);

        if ($cached instanceof PostWithComments) {
            return $cached;
        }

        $this->rateLimiter->waitForToken();

        $payload = $this->transport->get($this->buildUrl($path, $query));
        $postWithComments = $this->mapper->mapPostWithComments($this->expectListPayload($payload));

        $this->cache->set($cacheKey, $postWithComments);

        return $postWithComments;
    }

    private function fetchUserContentListing(
        string $username,
        string $listingType,
        ?PaginationOptions $options = null,
    ): UserContentListing {
        $normalizedUsername = $this->normalizeUsername($username);
        $encodedUsername = rawurlencode($normalizedUsername);
        $query = $this->buildPaginationQuery($options);
        $path = sprintf('/user/%s/%s.json', $encodedUsername, $listingType);
        $cacheKey = $this->buildCacheKey($path, $query);

        $cached = $this->cache->get($cacheKey);

        if ($cached instanceof UserContentListing) {
            return $cached;
        }

        $this->rateLimiter->waitForToken();

        $payload = $this->transport->get($this->buildUrl($path, $query));
        $listing = $this->mapper->mapUserContentListing($this->expectAssociativePayload($payload));

        $this->cache->set($cacheKey, $listing);

        return $listing;
    }

    /**
     * @param array<string, scalar> $extraQuery
     */
    private function fetchSearchResults(
        string $query,
        string $path,
        ?SearchOptions $options = null,
        array $extraQuery = [],
    ): SearchResults {
        $searchQuery = $this->buildSearchQuery($query, $options, $extraQuery);
        $cacheKey = $this->buildCacheKey($path, $searchQuery);
        $cached = $this->cache->get($cacheKey);

        if ($cached instanceof SearchResults) {
            return $cached;
        }

        $this->rateLimiter->waitForToken();

        $payload = $this->transport->get($this->buildUrl($path, $searchQuery));
        $results = $this->mapper->mapSearchResults($this->expectAssociativePayload($payload));

        $this->cache->set($cacheKey, $results);

        return $results;
    }

    /**
     * @return array<string, scalar>
     */
    private function buildListingQuery(?PaginationOptions $options, ?TopTimeRange $timeRange = null): array
    {
        $query = $this->buildPaginationQuery($options);

        if ($timeRange !== null) {
            $query['t'] = $timeRange->value;
        }

        return $query;
    }

    /**
     * @return array<string, scalar>
     */
    private function buildPaginationQuery(?PaginationOptions $options): array
    {
        $options ??= new PaginationOptions();

        $query = [];

        if ($options->after !== null) {
            $query['after'] = $options->after;
        }

        if ($options->before !== null) {
            $query['before'] = $options->before;
        }

        if ($options->count > 0) {
            $query['count'] = $options->count;
        }

        $query['limit'] = $options->limit;

        return $query;
    }

    /**
     * @return array<string, scalar>
     */
    private function buildCommentQuery(?CommentOptions $options): array
    {
        $options ??= new CommentOptions();

        $query = [];

        if ($options->after !== null) {
            $query['after'] = $options->after;
        }

        if ($options->before !== null) {
            $query['before'] = $options->before;
        }

        $query['limit'] = $options->limit;

        if ($options->depth !== null) {
            $query['depth'] = $options->depth;
        }

        $query['sort'] = $options->sort->value;

        return $query;
    }

    /**
     * @param array<string, scalar> $extraQuery
     *
     * @return array<string, scalar>
     */
    private function buildSearchQuery(string $query, ?SearchOptions $options, array $extraQuery = []): array
    {
        $options ??= new SearchOptions();
        $normalizedQuery = $this->normalizeSearchQuery($query);

        $parameters = ['q' => $normalizedQuery];

        foreach ($extraQuery as $key => $value) {
            $parameters[$key] = $value;
        }

        if ($options->after !== null) {
            $parameters['after'] = $options->after;
        }

        if ($options->before !== null) {
            $parameters['before'] = $options->before;
        }

        $parameters['limit'] = $options->limit;
        $parameters['sort'] = $options->sort->value;
        $parameters['t'] = $options->timeRange->value;

        return $parameters;
    }

    /**
     * @param array<string, scalar> $query
     */
    private function buildUrl(string $path, array $query): string
    {
        $baseUri = rtrim($this->config->baseUri, '/');
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        if ($queryString === '') {
            return sprintf('%s%s', $baseUri, $path);
        }

        return sprintf('%s%s?%s', $baseUri, $path, $queryString);
    }

    /**
     * @param array<string, scalar> $query
     */
    private function buildCacheKey(string $path, array $query): string
    {
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        if ($queryString === '') {
            return sprintf('get:%s', ltrim($path, '/'));
        }

        return sprintf('get:%s?%s', ltrim($path, '/'), $queryString);
    }

    private function normalizeSubredditName(string $name): string
    {
        $normalized = trim($name);
        $normalized = trim($normalized, '/');

        if (str_starts_with($normalized, 'r/')) {
            $normalized = substr($normalized, 2);
        }

        if ($normalized === '') {
            throw new InvalidArgumentException('The subreddit name cannot be empty.');
        }

        return $normalized;
    }

    private function normalizeUsername(string $username): string
    {
        $normalized = trim($username);
        $normalized = trim($normalized, '/');

        if (str_starts_with($normalized, 'u/')) {
            $normalized = substr($normalized, 2);
        }

        if ($normalized === '') {
            throw new InvalidArgumentException('The username cannot be empty.');
        }

        return $normalized;
    }

    private function normalizePostId(string $postId): string
    {
        $normalized = trim($postId);
        $normalized = trim($normalized, '/');

        if ($normalized === '') {
            throw new InvalidArgumentException('The post ID cannot be empty.');
        }

        return $normalized;
    }

    private function normalizeMultiredditName(string $multiName): string
    {
        $normalized = trim($multiName);
        $normalized = trim($normalized, '/');

        if (str_starts_with($normalized, 'm/')) {
            $normalized = substr($normalized, 2);
        }

        if ($normalized === '') {
            throw new InvalidArgumentException('The multireddit name cannot be empty.');
        }

        return $normalized;
    }

    private function normalizeSearchQuery(string $query): string
    {
        $normalized = trim($query);

        if ($normalized === '') {
            throw new InvalidArgumentException('The search query cannot be empty.');
        }

        return $normalized;
    }

    /**
     * @param array<mixed, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function expectAssociativePayload(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Expected an associative Reddit payload.');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<mixed, mixed> $payload
     *
     * @return list<array<string, mixed>>
     */
    private function expectListPayload(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $item) {
            $normalized[] = $this->expectAssociativePayload(
                is_array($item) ? $item : throw new InvalidArgumentException('Expected a Reddit payload list.'),
            );
        }

        return $normalized;
    }
}
