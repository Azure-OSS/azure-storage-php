<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\ApiVersion;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use Psr\Http\Message\UriInterface;

/**
 * @internal
 */
final class ClientFactory
{
    public function create(UriInterface $uri, ?StorageSharedKeyCredential $sharedKeyCredential): Client
    {
        $handlerStack = HandlerStack::create();

        $handlerStack->push(new AddXMsClientRequestIdMiddleware());
        $handlerStack->push(new AddXMsDateHeaderMiddleware());
        $handlerStack->push(new AddXMsVersionMiddleware(ApiVersion::LATEST));
        $handlerStack->push(new AddDefaultQueryParamsMiddleware($uri->getQuery()));

        if ($sharedKeyCredential !== null) {
            $handlerStack->push(new AddAuthorizationHeaderMiddleware($sharedKeyCredential));
        }

        $handlerStack->push($this->createRetryMiddleware());

        return new Client(['handler' => $handlerStack]);
    }

    /**
     * @see https://learn.microsoft.com/en-us/azure/architecture/best-practices/retry-service-specific#general-rest-and-retry-guidelines
     */
    private function createRetryMiddleware(): \Closure
    {
        return GuzzleRetryMiddleware::factory([
            'retry_on_status' => [
                408, // Request Timeout
                429, // Too Many Requests
                500, // Internal Server Error
                502, // Bad Gateway
                503, // Service Unavailable
                504, // Gateway Timeout
            ],
        ]);
    }
}
