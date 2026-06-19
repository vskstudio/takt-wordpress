<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress\Tests\Support;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/** A PSR-18 client that records the last request and returns 202, for asserting on the payload core-php sends. */
final class CapturingClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;
    public int $calls = 0;

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;
        ++$this->calls;

        return (new Psr17Factory())->createResponse(202);
    }

    /** @return array<string,mixed> */
    public function lastPayload(): array
    {
        return json_decode((string) $this->lastRequest?->getBody(), true) ?? [];
    }
}
