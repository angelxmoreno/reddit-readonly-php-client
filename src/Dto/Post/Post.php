<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\Post;

use Amoreno\RedditClient\Dto\Common\Flair;

final readonly class Post
{
    public function __construct(
        public string $subreddit,
        public string $subredditNamePrefixed,
        public string $subredditId,
        public string $id,
        public string $name,
        public string $author,
        public string $permalink,
        public float $created,
        public float $createdUtc,
        public int $score,
        public int $numComments,
        public ?string $title = null,
        public ?string $selftext = null,
        public ?string $thumbnail = null,
        public ?string $url = null,
        public ?string $domain = null,
        public ?int $ups = null,
        public ?int $downs = null,
        public ?bool $archived = null,
        public ?bool $locked = null,
        public ?bool $stickied = null,
        public ?bool $over18 = null,
        public ?bool $spoiler = null,
        public ?bool $isSelf = null,
        public ?bool $isVideo = null,
        public ?float $upvoteRatio = null,
        public ?Flair $authorFlair = null,
        public ?Flair $linkFlair = null,
        public bool|int|float|null $edited = null,
    ) {
    }
}
