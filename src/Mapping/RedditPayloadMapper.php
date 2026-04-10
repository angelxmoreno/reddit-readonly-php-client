<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Mapping;

use Amoreno\RedditClient\Dto\Comment\Comment;
use Amoreno\RedditClient\Dto\Comment\CommentListing;
use Amoreno\RedditClient\Dto\Comment\MoreComments;
use Amoreno\RedditClient\Dto\Post\Post;
use Amoreno\RedditClient\Dto\Post\PostListing;
use Amoreno\RedditClient\Dto\Post\PostWithComments;
use Amoreno\RedditClient\Dto\Subreddit\Subreddit;
use Amoreno\RedditClient\Dto\User\SearchResults;
use Amoreno\RedditClient\Dto\User\UserContentListing;
use Amoreno\RedditClient\Dto\User\UserProfile;
use Amoreno\RedditClient\Dto\User\UserSummary;
use Amoreno\RedditClient\Enum\ThingKind;
use Amoreno\RedditClient\Exception\ValidationError;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Configurator\ConvertKeysToCamelCase;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;

final readonly class RedditPayloadMapper
{
    private TreeMapper $mapper;

    public function __construct(?TreeMapper $mapper = null)
    {
        $this->mapper = $mapper
            ?? (new MapperBuilder())
                ->configureWith(new ConvertKeysToCamelCase())
                ->allowSuperfluousKeys()
                ->mapper();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapPost(array $payload): Post
    {
        return $this->map(Post::class, $this->extractThingData($payload, ThingKind::Post, 'post'), 'post');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapPostListing(array $payload): PostListing
    {
        $data = $this->extractThingData($payload, ThingKind::Listing, 'post listing');

        return new PostListing(
            modhash: $this->nullableString($data, 'modhash'),
            dist: $this->nullableInt($data, 'dist'),
            children: array_map($this->mapPost(...), $this->extractChildren($data, 'post listing')),
            after: $this->nullableString($data, 'after'),
            before: $this->nullableString($data, 'before'),
            geoFilter: $this->nullableString($data, 'geo_filter'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapCommentListing(array $payload): CommentListing
    {
        $data = $this->extractThingData($payload, ThingKind::Listing, 'comment listing');

        return new CommentListing(
            modhash: $this->nullableString($data, 'modhash'),
            dist: $this->nullableInt($data, 'dist'),
            children: array_map($this->mapCommentNode(...), $this->extractChildren($data, 'comment listing')),
            after: $this->nullableString($data, 'after'),
            before: $this->nullableString($data, 'before'),
        );
    }

    /**
     * @param list<array<string, mixed>> $payload
     */
    public function mapPostWithComments(array $payload): PostWithComments
    {
        if (!isset($payload[0], $payload[1]) || count($payload) !== 2) {
            throw new ValidationError('Invalid Reddit post-with-comments payload.');
        }

        return new PostWithComments(
            post: $this->extractSinglePost($this->mapPostListing($payload[0])),
            comments: $this->mapCommentListing($payload[1]),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapSubreddit(array $payload): Subreddit
    {
        return $this->map(
            Subreddit::class,
            $this->extractThingData($payload, ThingKind::Subreddit, 'subreddit'),
            'subreddit',
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapUserProfile(array $payload): UserProfile
    {
        return $this->map(
            UserProfile::class,
            $this->extractThingData($payload, ThingKind::User, 'user profile'),
            'user profile',
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapUserContentListing(array $payload): UserContentListing
    {
        $data = $this->extractThingData($payload, ThingKind::Listing, 'user content listing');

        return new UserContentListing(
            modhash: $this->nullableString($data, 'modhash'),
            dist: $this->nullableInt($data, 'dist'),
            children: array_map($this->mapUserContentNode(...), $this->extractChildren($data, 'user content listing')),
            after: $this->nullableString($data, 'after'),
            before: $this->nullableString($data, 'before'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapSearchResults(array $payload): SearchResults
    {
        $data = $this->extractThingData($payload, ThingKind::Listing, 'search results');

        return new SearchResults(
            modhash: $this->nullableString($data, 'modhash'),
            dist: $this->nullableInt($data, 'dist'),
            children: array_map($this->mapSearchResultNode(...), $this->extractChildren($data, 'search results')),
            after: $this->nullableString($data, 'after'),
            before: $this->nullableString($data, 'before'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapComment(array $payload): Comment
    {
        $data = $this->extractThingData($payload, ThingKind::Comment, 'comment');
        $normalized = $data;

        if (array_key_exists('replies', $data) && is_array($data['replies'])) {
            $normalized['replies'] = $this->mapCommentListing(
                $this->expectAssociativeArray($data['replies'], 'comment'),
            );
        }

        return $this->map(Comment::class, $normalized, 'comment');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapMoreComments(array $payload): MoreComments
    {
        return $this->map(
            MoreComments::class,
            $this->extractThingData($payload, ThingKind::More, 'more comments'),
            'more comments',
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function mapUserSummary(array $payload): UserSummary
    {
        return $this->map(
            UserSummary::class,
            $this->extractThingData($payload, ThingKind::User, 'user'),
            'user',
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $signature
     * @param array<string, mixed> $payload
     *
     * @return T
     */
    private function map(string $signature, array $payload, string $label): object
    {
        try {
            return $this->mapper->map($signature, $payload);
        } catch (MappingError $exception) {
            throw new ValidationError(
                sprintf('Invalid Reddit %s payload.', $label),
                previous: $exception,
            );
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function extractThingData(array $payload, ThingKind $expectedKind, string $label): array
    {
        $kind = $payload['kind'] ?? null;
        $data = $payload['data'] ?? null;

        if ($kind !== $expectedKind->value) {
            throw new ValidationError(sprintf('Invalid Reddit %s payload.', $label));
        }

        return $this->expectAssociativeArray($data, $label);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return list<array<string, mixed>>
     */
    private function extractChildren(array $data, string $label): array
    {
        $children = $data['children'] ?? null;

        if (!is_array($children)) {
            throw new ValidationError(sprintf('Invalid Reddit %s payload.', $label));
        }

        $normalized = [];

        foreach ($children as $child) {
            $normalized[] = $this->expectAssociativeArray($child, $label);
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function expectAssociativeArray(mixed $value, string $label): array
    {
        if (!is_array($value)) {
            throw new ValidationError(sprintf('Invalid Reddit %s payload.', $label));
        }

        $normalized = [];

        foreach ($value as $key => $_unused) {
            if (!is_string($key)) {
                throw new ValidationError(sprintf('Invalid Reddit %s payload.', $label));
            }

            $normalized[$key] = $value[$key];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function mapCommentNode(array $payload): Comment|MoreComments
    {
        $kind = $payload['kind'] ?? null;

        return match ($kind) {
            ThingKind::Comment->value => $this->mapComment($payload),
            ThingKind::More->value => $this->mapMoreComments($payload),
            default => throw new ValidationError('Invalid Reddit comment listing payload.'),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function mapUserContentNode(array $payload): Post|Comment
    {
        $kind = $payload['kind'] ?? null;

        return match ($kind) {
            ThingKind::Post->value => $this->mapPost($payload),
            ThingKind::Comment->value => $this->mapComment($payload),
            default => throw new ValidationError('Invalid Reddit user content listing payload.'),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function mapSearchResultNode(array $payload): Post|Comment|Subreddit|UserSummary
    {
        $kind = $payload['kind'] ?? null;

        return match ($kind) {
            ThingKind::Post->value => $this->mapPost($payload),
            ThingKind::Comment->value => $this->mapComment($payload),
            ThingKind::Subreddit->value => $this->mapSubreddit($payload),
            ThingKind::User->value => $this->mapUserSummary($payload),
            default => throw new ValidationError('Invalid Reddit search results payload.'),
        };
    }

    private function extractSinglePost(PostListing $listing): Post
    {
        if (!isset($listing->children[0])) {
            throw new ValidationError('Invalid Reddit post-with-comments payload.');
        }

        return $listing->children[0];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return is_int($value) ? $value : null;
    }
}
