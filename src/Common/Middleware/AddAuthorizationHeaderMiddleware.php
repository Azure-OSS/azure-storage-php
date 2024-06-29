<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\AuthScheme;
use Psr\Http\Message\RequestInterface;

final class AddAuthorizationHeaderMiddleware
{
    public function __construct(private AuthScheme $authScheme) {}

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $this->authScheme->signRequest($request);

            return $handler($request, $options);
        };
    }
}
