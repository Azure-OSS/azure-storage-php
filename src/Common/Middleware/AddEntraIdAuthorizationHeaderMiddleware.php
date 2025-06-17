<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\Auth\AccessToken;
use AzureOss\Storage\Common\Auth\TokenCredential;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
final class AddEntraIdAuthorizationHeaderMiddleware
{
    private ?AccessToken $cachedAccessToken = null;

    public function __construct(private readonly TokenCredential $tokenCredential) {}

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            if ($this->cachedAccessToken === null ||
                $this->expiresInAMinute($this->cachedAccessToken)
            ) {
                $this->cachedAccessToken = $this->tokenCredential->getToken();
            }

            $request = $request->withHeader('Authorization', 'Bearer ' . $this->cachedAccessToken->accessToken);

            return $handler($request, $options);
        };
    }

    private function expiresInAMinute(AccessToken $accessToken): bool
    {
        return $accessToken->expiresOn < (new \DateTimeImmutable())->add(new \DateInterval('PT1M'));
    }
}
