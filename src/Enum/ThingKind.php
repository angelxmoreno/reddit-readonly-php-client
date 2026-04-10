<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Enum;

enum ThingKind: string
{
    case Listing = 'Listing';
    case Comment = 't1';
    case User = 't2';
    case Post = 't3';
    case Subreddit = 't5';
    case More = 'more';
}
