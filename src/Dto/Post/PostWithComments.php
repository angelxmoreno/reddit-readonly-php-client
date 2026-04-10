<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\Post;

use Amoreno\RedditClient\Dto\Comment\CommentListing;

final readonly class PostWithComments
{
    public function __construct(
        public Post $post,
        public CommentListing $comments,
    ) {
    }
}
