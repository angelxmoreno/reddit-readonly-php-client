<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\User;

use Amoreno\RedditClient\Dto\Comment\Comment;
use Amoreno\RedditClient\Dto\Post\Post;

final readonly class UserContentListing
{
    /**
     * @param list<Post|Comment> $children
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
