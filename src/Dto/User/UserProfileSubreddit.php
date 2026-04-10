<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Dto\User;

final readonly class UserProfileSubreddit
{
    public function __construct(
        public string $displayName,
        public string $displayNamePrefixed,
        public string $title,
        public string $name,
        public string $url,
        public string $description,
        public string $publicDescription,
        public int $subscribers,
        public bool $restrictPosting,
        public bool $freeFormReports,
        public bool $showMedia,
        public bool $quarantine,
        public bool $acceptFollowers,
        public bool $linkFlairEnabled,
        public bool $disableContributorRequests,
        public bool $restrictCommenting,
        public string $subredditType,
        public bool $over18,
        public ?string $iconImg = null,
    ) {
    }
}
