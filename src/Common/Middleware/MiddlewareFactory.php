<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\Auth\StorageSharedKeyCredential;
use GuzzleHttp\HandlerStack;

final class MiddlewareFactory
{
    public function create(string $apiVersion, StorageSharedKeyCredential $sharedKeyCredential): HandlerStack
    {
        $handlerStack = HandlerStack::create();

        $handlerStack->push(new AddXMsDateHeaderMiddleware());
        $handlerStack->push(new AddXMsVersionMiddleware($apiVersion));
        $handlerStack->push(new AddAuthorizationHeaderMiddleware($sharedKeyCredential));

        return $handlerStack;
    }
}
