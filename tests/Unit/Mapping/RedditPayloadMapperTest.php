<?php

declare(strict_types=1);

use Amoreno\RedditClient\Dto\Comment\CommentListing;
use Amoreno\RedditClient\Dto\Comment\Comment;
use Amoreno\RedditClient\Dto\Comment\MoreComments;
use Amoreno\RedditClient\Dto\Post\Post;
use Amoreno\RedditClient\Dto\Post\PostWithComments;
use Amoreno\RedditClient\Dto\Subreddit\Subreddit;
use Amoreno\RedditClient\Dto\User\SearchResults;
use Amoreno\RedditClient\Dto\User\UserContentListing;
use Amoreno\RedditClient\Dto\User\UserProfile;
use Amoreno\RedditClient\Dto\User\UserSummary;
use Amoreno\RedditClient\Exception\ValidationError;
use Amoreno\RedditClient\Mapping\RedditPayloadMapper;

it('maps minimal valid payloads into dto objects', function (): void {
    $mapper = new RedditPayloadMapper();

    $post = $mapper->mapPost(minimalPostThingPayload());
    $subreddit = $mapper->mapSubreddit(minimalSubredditThingPayload());
    $user = $mapper->mapUserProfile(minimalUserProfileThingPayload());
    $userContent = $mapper->mapUserContentListing(minimalUserContentListingPayload());
    $postWithComments = $mapper->mapPostWithComments(minimalPostWithCommentsPayload());

    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->id)->toBe('post-1')
        ->and($subreddit)->toBeInstanceOf(Subreddit::class)
        ->and($subreddit->displayName)->toBe('php')
        ->and($user)->toBeInstanceOf(UserProfile::class)
        ->and($user->name)->toBe('angel')
        ->and($userContent)->toBeInstanceOf(UserContentListing::class)
        ->and($userContent->children[0])->toBeInstanceOf(Post::class)
        ->and($postWithComments)->toBeInstanceOf(PostWithComments::class)
        ->and($postWithComments->comments)->toBeInstanceOf(CommentListing::class);
});

it('maps mixed search result listings', function (): void {
    $mapper = new RedditPayloadMapper();

    $results = $mapper->mapSearchResults(minimalSearchResultsPayload());

    expect($results)->toBeInstanceOf(SearchResults::class)
        ->and($results->children)->toHaveCount(4)
        ->and($results->children[0])->toBeInstanceOf(Post::class)
        ->and($results->children[1])->toBeInstanceOf(Comment::class)
        ->and($results->children[2])->toBeInstanceOf(Subreddit::class)
        ->and($results->children[3])->toBeInstanceOf(UserSummary::class);
});

it('maps recursive comment listings with more nodes', function (): void {
    $mapper = new RedditPayloadMapper();

    $listing = $mapper->mapCommentListing(minimalCommentListingPayload());
    $firstChild = $listing->children[0];
    $secondChild = $listing->children[1];

    expect($firstChild)->toBeInstanceOf(Comment::class)
        ->and($secondChild)->toBeInstanceOf(MoreComments::class);

    if (!$firstChild instanceof Comment) {
        throw new RuntimeException('Expected a comment as the first child.');
    }

    $replies = $firstChild->replies;

    expect($replies)->toBeInstanceOf(CommentListing::class);

    if (!$replies instanceof CommentListing) {
        throw new RuntimeException('Expected recursive replies to map to a comment listing.');
    }

    expect($listing)->toBeInstanceOf(CommentListing::class)
        ->and($replies->children[0])->toBeInstanceOf(Comment::class);
});

it('fails fast on invalid kind values', function (): void {
    $mapper = new RedditPayloadMapper();
    $payload = minimalPostThingPayload();
    $payload['kind'] = 't9';

    expect(fn (): Post => $mapper->mapPost($payload))
        ->toThrow(ValidationError::class, 'Invalid Reddit post payload.');
});

it('fails fast when required fields are missing', function (): void {
    $mapper = new RedditPayloadMapper();
    $payload = minimalSubredditThingPayloadMissingDisplayName();

    expect(fn (): Subreddit => $mapper->mapSubreddit($payload))
        ->toThrow(ValidationError::class, 'Invalid Reddit subreddit payload.');
});

/**
 * @return array<string, mixed>
 */
function minimalPostThingPayload(): array
{
    return [
        'kind' => 't3',
        'data' => [
            'subreddit' => 'php',
            'subreddit_name_prefixed' => 'r/php',
            'subreddit_id' => 't5_2qh33',
            'id' => 'post-1',
            'name' => 't3_post-1',
            'author' => 'angel',
            'permalink' => '/r/php/comments/post-1/example-post/',
            'created' => 1_700_000_000,
            'created_utc' => 1_700_000_000,
            'score' => 42,
            'num_comments' => 3,
            'title' => 'Example post',
            'url' => 'https://www.reddit.com/r/php/comments/post-1/example-post/',
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalPostListingPayload(): array
{
    return [
        'kind' => 'Listing',
        'data' => [
            'modhash' => null,
            'dist' => 1,
            'children' => [minimalPostThingPayload()],
            'after' => null,
            'before' => null,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalCommentThingPayload(): array
{
    return [
        'kind' => 't1',
        'data' => [
            'subreddit' => 'php',
            'subreddit_name_prefixed' => 'r/php',
            'subreddit_id' => 't5_2qh33',
            'id' => 'comment-1',
            'name' => 't1_comment-1',
            'author' => 'angel',
            'body' => 'Nice post',
            'body_html' => '&lt;div&gt;Nice post&lt;/div&gt;',
            'parent_id' => 't3_post-1',
            'link_id' => 't3_post-1',
            'permalink' => '/r/php/comments/post-1/example-post/comment-1/',
            'created' => 1_700_000_010,
            'created_utc' => 1_700_000_010,
            'score' => 7,
            'depth' => 0,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalMoreCommentsPayload(): array
{
    return [
        'kind' => 'more',
        'data' => [
            'count' => 1,
            'name' => 't1_more-1',
            'id' => 'more-1',
            'parent_id' => 't3_post-1',
            'depth' => 1,
            'children' => ['comment-2'],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalCommentListingPayload(): array
{
    return [
        'kind' => 'Listing',
        'data' => [
            'modhash' => null,
            'dist' => 2,
            'children' => [
                [
                    'kind' => 't1',
                    'data' => [
                        'subreddit' => 'php',
                        'subreddit_name_prefixed' => 'r/php',
                        'subreddit_id' => 't5_2qh33',
                        'id' => 'comment-1',
                        'name' => 't1_comment-1',
                        'author' => 'angel',
                        'body' => 'Nice post',
                        'body_html' => '&lt;div&gt;Nice post&lt;/div&gt;',
                        'parent_id' => 't3_post-1',
                        'link_id' => 't3_post-1',
                        'permalink' => '/r/php/comments/post-1/example-post/comment-1/',
                        'created' => 1_700_000_010,
                        'created_utc' => 1_700_000_010,
                        'score' => 7,
                        'depth' => 0,
                        'replies' => [
                            'kind' => 'Listing',
                            'data' => [
                                'modhash' => null,
                                'dist' => 1,
                                'children' => [
                                    [
                                        'kind' => 't1',
                                        'data' => [
                                            'subreddit' => 'php',
                                            'subreddit_name_prefixed' => 'r/php',
                                            'subreddit_id' => 't5_2qh33',
                                            'id' => 'comment-2',
                                            'name' => 't1_comment-2',
                                            'author' => 'angel',
                                            'body' => 'Nested reply',
                                            'body_html' => '&lt;div&gt;Nested reply&lt;/div&gt;',
                                            'parent_id' => 't1_comment-1',
                                            'link_id' => 't3_post-1',
                                            'permalink' => '/r/php/comments/post-1/example-post/comment-2/',
                                            'created' => 1_700_000_020,
                                            'created_utc' => 1_700_000_020,
                                            'score' => 3,
                                            'depth' => 1,
                                        ],
                                    ],
                                ],
                                'after' => null,
                                'before' => null,
                            ],
                        ],
                    ],
                ],
                minimalMoreCommentsPayload(),
            ],
            'after' => null,
            'before' => null,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalSubredditThingPayload(): array
{
    return [
        'kind' => 't5',
        'data' => [
            'id' => '2qh33',
            'name' => 't5_2qh33',
            'display_name' => 'php',
            'display_name_prefixed' => 'r/php',
            'title' => 'PHP',
            'description' => 'PHP discussion',
            'public_description' => 'PHP discussion',
            'url' => '/r/php/',
            'subscribers' => 123_456,
            'created' => 1_600_000_000,
            'created_utc' => 1_600_000_000,
            'over18' => false,
            'quarantine' => false,
            'subreddit_type' => 'public',
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalSubredditThingPayloadMissingDisplayName(): array
{
    return [
        'kind' => 't5',
        'data' => [
            'id' => '2qh33',
            'name' => 't5_2qh33',
            'display_name_prefixed' => 'r/php',
            'title' => 'PHP',
            'description' => 'PHP discussion',
            'public_description' => 'PHP discussion',
            'url' => '/r/php/',
            'subscribers' => 123_456,
            'created' => 1_600_000_000,
            'created_utc' => 1_600_000_000,
            'over18' => false,
            'quarantine' => false,
            'subreddit_type' => 'public',
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalUserProfileSubredditPayload(): array
{
    return [
        'display_name' => 'u_angel',
        'display_name_prefixed' => 'u/angel',
        'title' => 'angel',
        'name' => 't5_profile',
        'url' => '/user/angel/',
        'description' => 'User profile',
        'public_description' => 'User profile',
        'subscribers' => 10,
        'restrict_posting' => true,
        'free_form_reports' => false,
        'show_media' => true,
        'quarantine' => false,
        'accept_followers' => true,
        'link_flair_enabled' => false,
        'disable_contributor_requests' => false,
        'restrict_commenting' => false,
        'subreddit_type' => 'public',
        'over_18' => false,
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalUserProfileThingPayload(): array
{
    return [
        'kind' => 't2',
        'data' => [
            'id' => 'user-1',
            'name' => 'angel',
            'created' => 1_600_000_000,
            'created_utc' => 1_600_000_000,
            'icon_img' => 'https://example.com/avatar.png',
            'is_employee' => false,
            'is_friend' => false,
            'is_gold' => false,
            'is_mod' => false,
            'verified' => true,
            'hide_from_robots' => false,
            'link_karma' => 100,
            'is_blocked' => false,
            'has_subscribed' => true,
            'subreddit' => minimalUserProfileSubredditPayload(),
            'comment_karma' => 50,
            'total_karma' => 150,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalUserSearchResultThingPayload(): array
{
    return [
        'kind' => 't2',
        'data' => [
            'id' => 'user-1',
            'name' => 'angel',
            'created' => 1_600_000_000,
            'created_utc' => 1_600_000_000,
            'icon_img' => 'https://example.com/avatar.png',
            'is_employee' => false,
            'is_friend' => false,
            'is_gold' => false,
            'is_mod' => false,
            'verified' => true,
            'hide_from_robots' => false,
            'link_karma' => 100,
            'comment_karma' => 50,
            'is_blocked' => false,
            'has_subscribed' => true,
            'subreddit' => minimalUserProfileSubredditPayload(),
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalUserContentListingPayload(): array
{
    return [
        'kind' => 'Listing',
        'data' => [
            'modhash' => null,
            'dist' => 2,
            'children' => [
                minimalPostThingPayload(),
                minimalCommentThingPayload(),
            ],
            'after' => null,
            'before' => null,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function minimalSearchResultsPayload(): array
{
    return [
        'kind' => 'Listing',
        'data' => [
            'modhash' => null,
            'dist' => 4,
            'children' => [
                minimalPostThingPayload(),
                minimalCommentThingPayload(),
                minimalSubredditThingPayload(),
                minimalUserSearchResultThingPayload(),
            ],
            'after' => null,
            'before' => null,
            'facets' => [],
        ],
    ];
}

/**
 * @return array{0: array<string, mixed>, 1: array<string, mixed>}
 */
function minimalPostWithCommentsPayload(): array
{
    return [
        minimalPostListingPayload(),
        minimalCommentListingPayload(),
    ];
}
