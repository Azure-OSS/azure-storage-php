<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
final class AddXMsClientRequestIdMiddleware
{
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader('x-ms-client-request-id', \bin2hex(\random_bytes(16)));

            return $handler($request, $options);
        };
    }
}
