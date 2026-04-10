<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\User;

final readonly class UserSummary extends BaseUser
{
    public function __construct(
        string $id,
        string $name,
        float $created,
        float $createdUtc,
        string $iconImg,
        bool $isEmployee,
        bool $isFriend,
        bool $isGold,
        bool $isMod,
        bool $verified,
        bool $hideFromRobots,
        int $linkKarma,
        int $commentKarma,
        bool $isBlocked,
        bool $hasSubscribed,
        UserProfileSubreddit $subreddit,
        ?string $snoovatarImg = null,
        ?bool $acceptFollowers = null,
    ) {
        parent::__construct(
            id: $id,
            name: $name,
            created: $created,
            createdUtc: $createdUtc,
            iconImg: $iconImg,
            isEmployee: $isEmployee,
            isFriend: $isFriend,
            isGold: $isGold,
            isMod: $isMod,
            verified: $verified,
            hideFromRobots: $hideFromRobots,
            linkKarma: $linkKarma,
            isBlocked: $isBlocked,
            hasSubscribed: $hasSubscribed,
            subreddit: $subreddit,
            commentKarma: $commentKarma,
            snoovatarImg: $snoovatarImg,
            acceptFollowers: $acceptFollowers,
        );
    }
}
