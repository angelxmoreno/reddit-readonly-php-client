<?php

declare(strict_types=1);

namespace Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class StreamClientException extends RuntimeException implements ClientExceptionInterface
{
}
