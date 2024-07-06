<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\ApiVersion;
use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

/**
 * @internal
 */
final class ClientFactory
{
    public function create(?StorageSharedKeyCredential $sharedKeyCredential): Client
    {
        $handlerStack = HandlerStack::create();

        $handlerStack->push(new AddXMsDateHeaderMiddleware());
        $handlerStack->push(new AddXMsVersionMiddleware(ApiVersion::LATEST));

        if ($sharedKeyCredential !== null) {
            $handlerStack->push(new AddAuthorizationHeaderMiddleware($sharedKeyCredential));
        }

        return new Client(['handler' => $handlerStack]);
    }
}
