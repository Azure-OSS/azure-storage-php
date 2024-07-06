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
            if ($request->hasHeader('x-ms-client-request-id')) {
                $request = $request->withHeader('x-ms-version', uniqid(more_entropy: true));
            }

            return $handler($request, $options);
        };
    }
}
