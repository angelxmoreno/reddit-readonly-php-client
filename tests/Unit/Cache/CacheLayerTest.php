<?php

declare(strict_types=1);

use Amoreno\RedditClient\Cache\CacheLayer;
use Amoreno\RedditClient\Exception\CacheError;
use Psr\SimpleCache\CacheInterface;

it('reports disabled when no cache backend is configured', function (): void {
    $cache = new CacheLayer();

    expect($cache->isEnabled())->toBeFalse()
        ->and($cache->get('missing', 'fallback'))->toBe('fallback');
});

it('prefixes keys and uses the default ttl on writes', function (): void {
    $store = new InMemoryCacheStore();
    $cache = new CacheLayer($store, ttl: 300, keyPrefix: 'reddit:');

    expect($cache->set('listing:php', ['kind' => 'Listing']))->toBeTrue()
        ->and($store->lastSetKey)->toBe('reddit:listing:php')
        ->and($store->lastTtl)->toBe(300)
        ->and($cache->get('listing:php'))->toBe(['kind' => 'Listing']);
});

it('supports custom ttl overrides and cache deletion', function (): void {
    $store = new InMemoryCacheStore();
    $cache = new CacheLayer($store, ttl: 300, keyPrefix: 'reddit:');
    $ttl = new DateInterval('PT15S');

    $cache->set('listing:php', ['kind' => 'Listing'], $ttl);

    expect($store->lastTtl)->toBe($ttl)
        ->and($cache->delete('listing:php'))->toBeTrue()
        ->and($cache->get('listing:php'))->toBeNull();
});

it('swallows cache backend failures by default', function (): void {
    $cache = new CacheLayer(new ThrowingCacheStore());

    expect($cache->get('listing:php', 'fallback'))->toBe('fallback')
        ->and($cache->set('listing:php', ['kind' => 'Listing']))->toBeFalse()
        ->and($cache->delete('listing:php'))->toBeFalse()
        ->and($cache->clear())->toBeFalse();
});

it('can throw cache errors when configured to do so', function (): void {
    $cache = new CacheLayer(new ThrowingCacheStore(), throwOnError: true);

    expect(fn (): bool => $cache->set('listing:php', ['kind' => 'Listing']))
        ->toThrow(CacheError::class, 'Cache set failed');
});

final class InMemoryCacheStore implements CacheInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $items = [];

    public string $lastSetKey = '';

    public null|int|DateInterval $lastTtl = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->lastSetKey = $key;
        $this->lastTtl = $ttl;
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
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
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

final class ThrowingCacheStore implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        throw new RuntimeException('boom');
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        throw new RuntimeException('boom');
    }

    public function delete(string $key): bool
    {
        throw new RuntimeException('boom');
    }

    public function clear(): bool
    {
        throw new RuntimeException('boom');
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        throw new RuntimeException('boom');
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        throw new RuntimeException('boom');
    }

    public function deleteMultiple(iterable $keys): bool
    {
        throw new RuntimeException('boom');
    }

    public function has(string $key): bool
    {
        throw new RuntimeException('boom');
    }
}
