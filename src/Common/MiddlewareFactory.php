<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common;

use AzureOss\Storage\Common\Auth\Credentials;
use AzureOss\Storage\Common\Middleware\AddAuthorizationHeaderMiddleware;
use AzureOss\Storage\Common\Middleware\AddContentHeadersMiddleware;
use AzureOss\Storage\Common\Middleware\AddXMsDateHeaderMiddleware;
use AzureOss\Storage\Common\Middleware\AddXMsVersionMiddleware;
use GuzzleHttp\HandlerStack;

class MiddlewareFactory
{
    public function create(string $apiVersion, Credentials $credentials): HandlerStack
    {
        $handlerStack = HandlerStack::create();

        $handlerStack->push(new AddContentHeadersMiddleware());
        $handlerStack->push(new AddXMsDateHeaderMiddleware());
        $handlerStack->push(new AddXMsVersionMiddleware($apiVersion));
        $handlerStack->push(new AddAuthorizationHeaderMiddleware($credentials));

        return $handlerStack;
    }
}
