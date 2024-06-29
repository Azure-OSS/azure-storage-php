<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\ApiConstants;
use Psr\Http\Message\RequestInterface;

final class AddXMsVersionMiddleware
{
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader("x-ms-version", ApiConstants::VERSION);

            return $handler($request, $options);
        };
    }

}
