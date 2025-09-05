<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\ApiVersion;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use AzureOss\Storage\Common\Auth\TokenCredential;
use AzureOss\Storage\Common\Exceptions\RequestExceptionDeserializer;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleRetry\GuzzleRetryMiddleware;
use Psr\Http\Message\UriInterface;

/**
 * @internal
 */
final class ClientFactory
{
    public function create(?UriInterface $uri = null, StorageSharedKeyCredential|TokenCredential|null $credential = null, ?RequestExceptionDeserializer $exceptionDeserializer = null, HttpClientOptions $options = new HttpClientOptions()): Client
    {
        $handlerStack = HandlerStack::create();

        if ($exceptionDeserializer !== null) {
            $handlerStack->before('http_errors', new DeserializeExceptionMiddleware($exceptionDeserializer));
        }

        $handlerStack->push(new AddXMsClientRequestIdMiddleware());
        $handlerStack->push(new AddXMsDateHeaderMiddleware());
        $handlerStack->push(new AddXMsVersionMiddleware(ApiVersion::LATEST));

        if ($uri !== null) {
            $handlerStack->push(new AddDefaultQueryParamsMiddleware($uri->getQuery()));
        }

        if ($credential instanceof StorageSharedKeyCredential) {
            $handlerStack->push(new AddSharedKeyAuthorizationHeaderMiddleware($credential));
        } elseif ($credential instanceof TokenCredential) {
            $handlerStack->push(new AddEntraIdAuthorizationHeaderMiddleware($credential));
        }

        $handlerStack->push($this->createRetryMiddleware());

        return new Client(array_merge(['handler' => $handlerStack], $options->toGuzzleHttpClientConfig()));
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
