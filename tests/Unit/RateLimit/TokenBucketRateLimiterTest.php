<?php

declare(strict_types=1);

use Amoreno\RedditClient\RateLimit\TokenBucketRateLimiter;

it('starts with the configured burst size', function (): void {
    $limiter = createFakeLimiter(requestsPerMinute: 60, burstSize: 3);

    expect($limiter->getRemainingTokens())->toBe(3.0)
        ->and($limiter->getTimeUntilNextToken())->toBe(0);
});

it('consumes an available token immediately', function (): void {
    $limiter = createFakeLimiter(requestsPerMinute: 60, burstSize: 2);

    $limiter->waitForToken();

    expect($limiter->getRemainingTokens())->toBe(1.0)
        ->and($limiter->getRecordedSleepMilliseconds())->toBe(0);
});

it('waits until the next token becomes available when exhausted', function (): void {
    $limiter = createFakeLimiter(requestsPerMinute: 60, burstSize: 1);

    $limiter->waitForToken();
    $limiter->waitForToken();

    expect($limiter->getRecordedSleepMilliseconds())->toBe(1000)
        ->and($limiter->getRemainingTokens())->toBe(0.0)
        ->and($limiter->getTimeUntilNextToken())->toBe(1000);
});

it('refills tokens over time up to the configured capacity', function (): void {
    $limiter = createFakeLimiter(requestsPerMinute: 120, burstSize: 2);

    $limiter->waitForToken();
    $limiter->waitForToken();
    $limiter->advanceTimeBySeconds(0.5);

    expect($limiter->getRemainingTokens())->toBe(1.0);

    $limiter->advanceTimeBySeconds(5);

    expect($limiter->getRemainingTokens())->toBe(2.0);
});

function createFakeLimiter(int $requestsPerMinute, int $burstSize): FakeTokenBucketRateLimiter
{
    return new FakeTokenBucketRateLimiter($requestsPerMinute, $burstSize);
}

final class FakeTokenBucketRateLimiter extends TokenBucketRateLimiter
{
    private float $now = 0.0;

    private int $recordedSleepMilliseconds = 0;

    public function advanceTimeBySeconds(float $seconds): void
    {
        $this->now += $seconds;
    }

    public function getRecordedSleepMilliseconds(): int
    {
        return $this->recordedSleepMilliseconds;
    }

    protected function currentTimeInSeconds(): float
    {
        return $this->now;
    }

    protected function sleepMilliseconds(int $milliseconds): void
    {
        $this->recordedSleepMilliseconds += $milliseconds;
        $this->now += $milliseconds / 1000;
    }
}
