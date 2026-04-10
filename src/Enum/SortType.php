<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Enum;

enum SortType: string
{
    case Hot = 'hot';
    case New = 'new';
    case Top = 'top';
    case Rising = 'rising';
}
