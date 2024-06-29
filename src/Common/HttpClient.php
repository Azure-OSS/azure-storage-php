<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common;

use AzureOss\Storage\Common\Middleware\AddAuthorizationHeaderMiddleware;
use AzureOss\Storage\Common\Middleware\AddContentHeadersMiddleware;
use AzureOss\Storage\Common\Middleware\AddXMsDateHeaderMiddleware;
use AzureOss\Storage\Common\Middleware\AddXMsVersionMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Psr\Http\Client\ClientInterface;
use GrahamCampbell\GuzzleFactory\GuzzleFactory as RetryMiddlewareFactory;

final class HttpClient
{
    public static function create(): ClientInterface
    {
        $handlerStack = HandlerStack::create();
        $handlerStack->push(new AddXMsVersionMiddleware());
        $handlerStack->push(new AddXMsDateHeaderMiddleware());
        $handlerStack->push(new AddContentHeadersMiddleware());
//            $handlerStack->push(new AddAuthorizationHeaderMiddleware($this->authScheme)); // @todo
        RetryMiddlewareFactory::handler(stack: $handlerStack);

        return new Client(['handler' => $handlerStack]);
    }

}
