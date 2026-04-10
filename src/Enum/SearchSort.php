<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Enum;

enum SearchSort: string
{
    case Relevance = 'relevance';
    case Hot = 'hot';
    case Top = 'top';
    case New = 'new';
    case Comments = 'comments';
}
