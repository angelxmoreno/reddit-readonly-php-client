<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\Post;

final readonly class PostListing
{
    /**
     * @param list<Post> $children
     */
    public function __construct(
        public ?string $modhash,
        public ?int $dist,
        public array $children,
        public ?string $after,
        public ?string $before,
        public ?string $geoFilter = null,
    ) {
    }
}
