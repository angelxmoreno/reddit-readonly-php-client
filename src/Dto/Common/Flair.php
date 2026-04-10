<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\Common;

final readonly class Flair
{
    public function __construct(
        public ?string $text = null,
        public ?string $type = null,
        public ?string $textColor = null,
        public ?string $backgroundColor = null,
        public ?string $templateId = null,
    ) {
    }
}
