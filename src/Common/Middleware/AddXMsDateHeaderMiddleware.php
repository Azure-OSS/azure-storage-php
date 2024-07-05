<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
final class AddXMsDateHeaderMiddleware
{
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader('x-ms-date', gmdate('D, d M Y H:i:s T', time()));

            return $handler($request, $options);
        };
    }
}
