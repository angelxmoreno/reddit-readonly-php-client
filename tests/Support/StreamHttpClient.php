<?php

declare(strict_types=1);

namespace Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class StreamHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $context = stream_context_create([
            'http' => [
                'method' => $request->getMethod(),
                'header' => $this->buildHeaders($request),
                'ignore_errors' => true,
                'timeout' => 30,
            ],
        ]);

        $body = @file_get_contents((string) $request->getUri(), false, $context);

        if ($body === false) {
            throw new StreamClientException(
                sprintf('Failed to fetch "%s" with the stream HTTP client.', (string) $request->getUri()),
            );
        }

        /** @var list<string> $responseHeaders */
        $responseHeaders = $http_response_header;

        if (!isset($responseHeaders[0])) {
            throw new StreamClientException(
                sprintf('Failed to read HTTP headers for "%s".', (string) $request->getUri()),
            );
        }

        return new Response(
            $this->extractStatusCode($responseHeaders[0]),
            $this->extractHeaders($responseHeaders),
            $body,
        );
    }

    private function buildHeaders(RequestInterface $request): string
    {
        $headers = [];

        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = sprintf('%s: %s', $name, $value);
            }
        }

        return implode("\r\n", $headers);
    }

    /**
     * @param list<string> $responseHeaders
     *
     * @return array<string, list<string>>
     */
    private function extractHeaders(array $responseHeaders): array
    {
        $headers = [];

        foreach ($responseHeaders as $index => $headerLine) {
            if ($index === 0 || !str_contains($headerLine, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $headerLine, 2);
            $headers[trim($name)][] = trim($value);
        }

        return $headers;
    }

    private function extractStatusCode(string $statusLine): int
    {
        if (preg_match('/\s(\d{3})\s/', $statusLine, $matches) !== 1) {
            throw new StreamClientException(sprintf('Could not parse HTTP status line "%s".', $statusLine));
        }

        return (int) $matches[1];
    }
}
