<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Client;

use Amoreno\RedditClient\Cache\CacheLayer;
use Amoreno\RedditClient\Config\PaginationOptions;
use Amoreno\RedditClient\Config\RedditClientConfig;
use Amoreno\RedditClient\Dto\Post\PostListing;
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

    private function fetchSubredditListing(
        string $subreddit,
        string $sort,
        ?PaginationOptions $options = null,
        ?TopTimeRange $timeRange = null,
    ): PostListing {
        $normalizedSubreddit = $this->normalizeSubredditName($subreddit);
        $query = $this->buildListingQuery($options, $timeRange);
        $path = sprintf('/r/%s/%s.json', $normalizedSubreddit, $sort);
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
     * @return array<string, scalar>
     */
    private function buildListingQuery(?PaginationOptions $options, ?TopTimeRange $timeRange = null): array
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

        if ($timeRange !== null) {
            $query['t'] = $timeRange->value;
        }

        return $query;
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
}
