<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\Auth\AuthScheme;
use Psr\Http\Message\RequestInterface;

class AddAuthorizationHeaderMiddleware
{
    public function __construct(private AuthScheme $authScheme)
    {
    }

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $request = $request->withHeader('Authorization', $this->authScheme->computeAuthorizationHeader($request));

            return $handler($request, $options);
        };
    }
}
