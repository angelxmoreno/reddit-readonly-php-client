<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\User;

abstract readonly class BaseUser
{
    public function __construct(
        public string $id,
        public string $name,
        public float $created,
        public float $createdUtc,
        public string $iconImg,
        public bool $isEmployee,
        public bool $isFriend,
        public bool $isGold,
        public bool $isMod,
        public bool $verified,
        public bool $hideFromRobots,
        public int $linkKarma,
        public bool $isBlocked,
        public bool $hasSubscribed,
        public UserProfileSubreddit $subreddit,
        public ?int $commentKarma = null,
        public ?string $snoovatarImg = null,
        public ?bool $acceptFollowers = null,
    ) {
    }
}
