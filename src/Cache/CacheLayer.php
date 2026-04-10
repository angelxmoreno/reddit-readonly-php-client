<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Cache;

use Amoreno\RedditClient\Exception\CacheError;
use DateInterval;
use InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Throwable;

final readonly class CacheLayer
{
    /**
     * @param null|int|DateInterval $ttl
     */
    public function __construct(
        private ?CacheInterface $cache = null,
        private bool $enabled = true,
        private null|int|DateInterval $ttl = null,
        private string $keyPrefix = 'reddit-readonly-client:',
        private bool $throwOnError = false,
    ) {
        if (is_int($this->ttl) && $this->ttl < 1) {
            throw new InvalidArgumentException('The cache TTL must be greater than zero when provided.');
        }

        if ($this->keyPrefix === '') {
            throw new InvalidArgumentException('The cache key prefix cannot be empty.');
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->isEnabled()) {
            return $default;
        }

        try {
            return $this->cache?->get($this->prefixKey($key), $default) ?? $default;
        } catch (Throwable $exception) {
            $this->handleCacheError('get', $key, $exception);

            return $default;
        }
    }

    /**
     * @param null|int|DateInterval $ttl
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            return $this->cache?->set($this->prefixKey($key), $value, $ttl ?? $this->ttl) ?? false;
        } catch (Throwable $exception) {
            $this->handleCacheError('set', $key, $exception);

            return false;
        }
    }

    public function delete(string $key): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            return $this->cache?->delete($this->prefixKey($key)) ?? false;
        } catch (Throwable $exception) {
            $this->handleCacheError('delete', $key, $exception);

            return false;
        }
    }

    public function clear(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            return $this->cache?->clear() ?? false;
        } catch (Throwable $exception) {
            $this->handleCacheError('clear', '*', $exception);

            return false;
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->cache !== null;
    }

    public function prefixKey(string $key): string
    {
        return sprintf('%s%s', $this->keyPrefix, $key);
    }

    private function handleCacheError(string $operation, string $key, Throwable $exception): void
    {
        if (!$this->throwOnError) {
            return;
        }

        throw new CacheError(
            sprintf('Cache %s failed for key "%s".', $operation, $key),
            previous: $exception,
        );
    }
}
