<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Enum;

enum CommentSort: string
{
    case Confidence = 'confidence';
    case Top = 'top';
    case New = 'new';
    case Controversial = 'controversial';
    case Old = 'old';
    case Qa = 'qa';
    case Live = 'live';
}
