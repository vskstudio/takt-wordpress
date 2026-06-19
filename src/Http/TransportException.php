<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress\Http;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Raised when WordPress' HTTP layer returns a WP_Error (DNS failure, timeout,
 * refused connection…), surfaced as the PSR-18 network exception so the core-php
 * Takt client treats it like any other transport failure.
 */
final class TransportException extends \RuntimeException implements NetworkExceptionInterface
{
    public function __construct(private RequestInterface $request, string $message)
    {
        parent::__construct($message);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
