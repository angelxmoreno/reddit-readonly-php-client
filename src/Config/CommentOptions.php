<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Config;

use Amoreno\RedditClient\Enum\CommentSort;
use InvalidArgumentException;

final readonly class CommentOptions
{
    public function __construct(
        public CommentSort $sort = CommentSort::Confidence,
        public ?string $after = null,
        public ?string $before = null,
        public int $limit = 25,
        public ?int $depth = null,
    ) {
        if ($this->after !== null && $this->before !== null) {
            throw new InvalidArgumentException('Only one pagination cursor can be set at a time.');
        }

        if ($this->limit < 1 || $this->limit > 100) {
            throw new InvalidArgumentException('The limit must be between 1 and 100.');
        }

        if ($this->depth !== null && $this->depth < 1) {
            throw new InvalidArgumentException('The depth must be greater than zero when provided.');
        }
    }
}
