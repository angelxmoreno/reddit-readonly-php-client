<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Enum;

enum TopTimeRange: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
    case All = 'all';
}
