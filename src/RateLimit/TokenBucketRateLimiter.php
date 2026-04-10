<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\RateLimit;

use InvalidArgumentException;

class TokenBucketRateLimiter
{
    private float $tokens;

    private float $lastRefillAt;

    private readonly int $capacity;

    private readonly float $refillRatePerSecond;

    public function __construct(int $requestsPerMinute, int $burstSize)
    {
        if ($requestsPerMinute < 1) {
            throw new InvalidArgumentException('The requests per minute must be greater than zero.');
        }

        if ($burstSize < 1) {
            throw new InvalidArgumentException('The burst size must be greater than zero.');
        }

        $this->tokens = (float) $burstSize;
        $this->lastRefillAt = $this->currentTimeInSeconds();
        $this->capacity = $burstSize;
        $this->refillRatePerSecond = $requestsPerMinute / 60;
    }

    public function waitForToken(): void
    {
        $this->refillTokens();

        if ($this->tokens >= 1.0) {
            $this->tokens -= 1.0;

            return;
        }

        $timeToWait = $this->timeUntilNextTokenMilliseconds();
        $this->sleepMilliseconds($timeToWait);

        $this->waitForToken();
    }

    public function getRemainingTokens(): float
    {
        $this->refillTokens();

        return $this->tokens;
    }

    public function getTimeUntilNextToken(): int
    {
        $this->refillTokens();

        if ($this->tokens >= 1.0) {
            return 0;
        }

        return $this->timeUntilNextTokenMilliseconds();
    }

    protected function currentTimeInSeconds(): float
    {
        return microtime(true);
    }

    protected function sleepMilliseconds(int $milliseconds): void
    {
        if ($milliseconds <= 0) {
            return;
        }

        usleep($milliseconds * 1000);
    }

    private function refillTokens(): void
    {
        $now = $this->currentTimeInSeconds();
        $timePassed = $now - $this->lastRefillAt;
        $tokensToAdd = $timePassed * $this->refillRatePerSecond;

        $this->tokens = min($this->capacity, $this->tokens + $tokensToAdd);
        $this->lastRefillAt = $now;
    }

    private function timeUntilNextTokenMilliseconds(): int
    {
        $tokensNeeded = 1.0 - $this->tokens;

        return (int) ceil(($tokensNeeded / $this->refillRatePerSecond) * 1000);
    }
}
