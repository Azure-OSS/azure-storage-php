<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\Auth\WorkloadIdentityCredential;
use Psr\Http\Message\RequestInterface;

/**
 * Middleware to add Bearer token authorization header for Azure AD authentication
 * 
 * @internal
 */
final class AddBearerTokenMiddleware
{
    public function __construct(
        private readonly WorkloadIdentityCredential $workloadIdentityCredential
    ) {}

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            try {
                $accessToken = $this->workloadIdentityCredential->getAccessToken();
                $request = $request->withHeader('Authorization', "Bearer $accessToken");
            } catch (\Throwable $e) {
                // Log the error and fail the request immediately
                error_log("Failed to get access token for Azure Storage: " . $e->getMessage());
                throw new \RuntimeException("Azure AD authentication failed: " . $e->getMessage(), 0, $e);
            }

            return $handler($request, $options);
        };
    }
}