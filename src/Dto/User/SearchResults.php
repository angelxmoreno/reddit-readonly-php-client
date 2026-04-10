<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\User;

use Amoreno\RedditClient\Dto\Comment\Comment;
use Amoreno\RedditClient\Dto\Post\Post;
use Amoreno\RedditClient\Dto\Subreddit\Subreddit;

final readonly class SearchResults
{
    /**
     * @param list<Post|Comment|Subreddit|UserSummary> $children
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
