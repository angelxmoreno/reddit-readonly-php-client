<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\Comment;

final readonly class CommentListing
{
    /**
     * @param list<Comment|MoreComments> $children
     */
    public function __construct(
        public ?string $modhash,
        public ?int $dist,
        public array $children,
        public ?string $after,
        public ?string $before,
    ) {
    }
}
