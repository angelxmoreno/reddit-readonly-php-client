<?php

declare(strict_types=1);

use Amoreno\RedditClient\Config\RedditClientConfig;
use Amoreno\RedditClient\Exception\NetworkError;
use Amoreno\RedditClient\Exception\RedditApiError;
use Amoreno\RedditClient\Exception\ValidationError;
use Amoreno\RedditClient\Http\RedditTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

it('creates a get request with reddit json headers', function (): void {
    $httpClient = new FakeHttpClient(
        new Response(200, ['Content-Type' => 'application/json; charset=utf-8'], '{"kind":"Listing"}'),
    );

    $transport = new RedditTransport(
        $httpClient,
        new Psr17Factory(),
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    expect($transport->get('https://www.reddit.com/r/php.json'))
        ->toBe(['kind' => 'Listing'])
        ->and($httpClient->lastRequest)->not->toBeNull()
        ->and($httpClient->lastRequest?->getMethod())->toBe('GET')
        ->and((string) $httpClient->lastRequest?->getUri())->toBe('https://www.reddit.com/r/php.json')
        ->and($httpClient->lastRequest?->getHeaderLine('User-Agent'))->toBe('test-agent')
        ->and($httpClient->lastRequest?->getHeaderLine('Accept'))->toBe('application/json');
});

it('rejects non-success responses with a reddit api error', function (): void {
    $transport = new RedditTransport(
        new FakeHttpClient(
            new Response(404, ['Content-Type' => 'application/json'], '{"message":"nope"}', '1.1', 'Not Found'),
        ),
        new Psr17Factory(),
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    expect(fn (): array => $transport->get('https://www.reddit.com/r/php.json'))
        ->toThrow(RedditApiError::class, '404 Not Found');
});

it('rejects non-json responses', function (): void {
    $transport = new RedditTransport(
        new FakeHttpClient(
            new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], '<html></html>'),
        ),
        new Psr17Factory(),
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    expect(fn (): array => $transport->get('https://www.reddit.com/r/php.json'))
        ->toThrow(ValidationError::class, 'Expected a JSON response');
});

it('rejects invalid json payloads', function (): void {
    $transport = new RedditTransport(
        new FakeHttpClient(
            new Response(200, ['Content-Type' => 'application/json'], '{"kind":'),
        ),
        new Psr17Factory(),
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    expect(fn (): array => $transport->get('https://www.reddit.com/r/php.json'))
        ->toThrow(ValidationError::class, 'Failed to decode the Reddit JSON response');
});

it('wraps transport failures as network errors', function (): void {
    $transport = new RedditTransport(
        new FakeHttpClient(exception: new FakeClientException('boom')),
        new Psr17Factory(),
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    expect(fn (): array => $transport->get('https://www.reddit.com/r/php.json'))
        ->toThrow(NetworkError::class, 'Failed to send Reddit request');
});

it('does not mask request factory failures as network errors', function (): void {
    $transport = new RedditTransport(
        new FakeHttpClient(
            new Response(200, ['Content-Type' => 'application/json'], '{"kind":"Listing"}'),
        ),
        new class () implements \Psr\Http\Message\RequestFactoryInterface {
            public function createRequest(string $method, $uri): RequestInterface
            {
                throw new RuntimeException('bad request factory');
            }
        },
        new RedditClientConfig(userAgent: 'test-agent'),
    );

    expect(fn (): array => $transport->get('https://www.reddit.com/r/php.json'))
        ->toThrow(RuntimeException::class, 'bad request factory');
});

final class FakeHttpClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;

    public function __construct(
        private readonly ?ResponseInterface $response = null,
        private readonly ?ClientExceptionInterface $exception = null,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->response === null) {
            throw new FakeClientException('No fake response configured.');
        }

        return $this->response;
    }
}

final class FakeClientException extends RuntimeException implements ClientExceptionInterface
{
}
