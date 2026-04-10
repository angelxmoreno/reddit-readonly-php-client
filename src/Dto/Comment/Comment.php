<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\Comment;

use Amoreno\RedditClient\Dto\Common\Flair;

final readonly class Comment
{
    public function __construct(
        public string $subreddit,
        public string $subredditNamePrefixed,
        public string $subredditId,
        public string $id,
        public string $name,
        public string $author,
        public string $body,
        public string $bodyHtml,
        public string $parentId,
        public string $linkId,
        public string $permalink,
        public float $created,
        public float $createdUtc,
        public int $score,
        public ?bool $archived = null,
        public ?int $depth = null,
        public ?bool $stickied = null,
        public ?bool $collapsed = null,
        public ?bool $isSubmitter = null,
        public ?bool $isRoot = null,
        public ?Flair $authorFlair = null,
        public string|CommentListing|null $replies = null,
        public bool|int|float|null $edited = null,
    ) {
    }
}
