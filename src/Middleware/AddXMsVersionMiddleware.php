<?php

declare(strict_types=1);

namespace AzureOss\Storage\Middleware;

use Psr\Http\Message\RequestInterface;

class AddXMsVersionMiddleware
{
    public function __construct(
        private string $version
    ) {
    }

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader('x-ms-version', $this->version);

            return $handler($request, $options);
        };
    }
}
