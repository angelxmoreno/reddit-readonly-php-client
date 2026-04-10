<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Config;

use InvalidArgumentException;

final readonly class RedditClientConfig
{
    public function __construct(
        public string $baseUri = 'https://www.reddit.com',
        public string $userAgent = 'amoreno/reddit-readonly-php-client',
        public int $requestsPerMinute = 60,
        public int $burstSize = 10,
        public ?int $cacheTtl = 300,
        public string $cacheKeyPrefix = 'reddit-readonly-client',
    ) {
        if ($this->baseUri === '') {
            throw new InvalidArgumentException('The base URI cannot be empty.');
        }

        if ($this->userAgent === '') {
            throw new InvalidArgumentException('The user agent cannot be empty.');
        }

        if ($this->requestsPerMinute < 1) {
            throw new InvalidArgumentException('The requests per minute must be greater than zero.');
        }

        if ($this->burstSize < 1) {
            throw new InvalidArgumentException('The burst size must be greater than zero.');
        }

        if ($this->cacheTtl !== null && $this->cacheTtl < 1) {
            throw new InvalidArgumentException('The cache TTL must be greater than zero when provided.');
        }

        if ($this->cacheKeyPrefix === '') {
            throw new InvalidArgumentException('The cache key prefix cannot be empty.');
        }
    }
}
