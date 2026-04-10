<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\Subreddit;

final readonly class Subreddit
{
    public function __construct(
        public string $id,
        public string $name,
        public string $displayName,
        public string $displayNamePrefixed,
        public string $title,
        public string $description,
        public string $publicDescription,
        public string $url,
        public int $subscribers,
        public float $created,
        public float $createdUtc,
        public bool $over18,
        public bool $quarantine,
        public string $subredditType,
        public ?string $iconImg = null,
        public ?string $communityIcon = null,
    ) {
    }
}
