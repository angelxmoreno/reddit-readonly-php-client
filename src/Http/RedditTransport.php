<?php

declare(strict_types=1);

namespace Amoreno\RedditClient\Http;

use Amoreno\RedditClient\Config\RedditClientConfig;
use Amoreno\RedditClient\Exception\NetworkError;
use Amoreno\RedditClient\Exception\RedditApiError;
use Amoreno\RedditClient\Exception\ValidationError;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final readonly class RedditTransport
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private RedditClientConfig $config,
    ) {
    }

    /**
     * @return array<mixed, mixed>
     */
    public function get(string $url): array
    {
        try {
            $request = $this->requestFactory
                ->createRequest('GET', $url)
                ->withHeader('User-Agent', $this->config->userAgent)
                ->withHeader('Accept', 'application/json');

            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new NetworkError(
                sprintf('Failed to send Reddit request to "%s".', $url),
                previous: $exception,
            );
        }

        $this->assertSuccessfulResponse($response, $url);

        return $this->decodeJsonResponse($response, $url);
    }

    private function assertSuccessfulResponse(ResponseInterface $response, string $url): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 200 && $statusCode < 300) {
            return;
        }

        $reasonPhrase = $response->getReasonPhrase();
        $message = $reasonPhrase === ''
            ? sprintf('Reddit request to "%s" failed with status %d.', $url, $statusCode)
            : sprintf('Reddit request to "%s" failed with status %d %s.', $url, $statusCode, $reasonPhrase);

        throw new RedditApiError($message);
    }

    /**
     * @return array<mixed, mixed>
     */
    private function decodeJsonResponse(ResponseInterface $response, string $url): array
    {
        $contentType = $response->getHeaderLine('Content-Type');

        if (!str_contains(strtolower($contentType), 'application/json')) {
            $reportedContentType = $contentType === '' ? 'unknown content type' : $contentType;

            throw new ValidationError(
                sprintf('Expected a JSON response from "%s", got "%s".', $url, $reportedContentType),
            );
        }

        try {
            $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ValidationError(
                sprintf('Failed to decode the Reddit JSON response from "%s".', $url),
                previous: $exception,
            );
        }

        if (!is_array($decoded)) {
            throw new ValidationError(
                sprintf('Expected the Reddit response from "%s" to decode into an array.', $url),
            );
        }

        return $decoded;
    }
}
