<?php

declare(strict_types=1);

namespace Vskstudio\Takt\WordPress\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client backed by WordPress' own HTTP API (wp_remote_post), so the
 * plugin needs no bundled HTTP transport (Guzzle et al.). Injected into the
 * core-php Takt S2S client.
 */
final class WpRemotePostClient implements ClientInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(?ResponseFactoryInterface $responseFactory = null)
    {
        $this->responseFactory = $responseFactory ?? new Psr17Factory();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        $result = \wp_remote_post((string) $request->getUri(), [
            'method' => $request->getMethod(),
            'headers' => $headers,
            'body' => (string) $request->getBody(),
            'timeout' => 5,
            'redirection' => 0,
        ]);

        if (\is_wp_error($result)) {
            throw new TransportException($request, (string) $result->get_error_message());
        }

        $code = (int) \wp_remote_retrieve_response_code($result);
        $response = $this->responseFactory->createResponse($code !== 0 ? $code : 502);
        $response->getBody()->write((string) \wp_remote_retrieve_body($result));

        return $response;
    }
}
