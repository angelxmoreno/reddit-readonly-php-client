<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\Comment;

final readonly class MoreComments
{
    /**
     * @param list<string> $children
     */
    public function __construct(
        public int $count,
        public string $name,
        public string $id,
        public string $parentId,
        public int $depth,
        public array $children,
    ) {
    }
}
