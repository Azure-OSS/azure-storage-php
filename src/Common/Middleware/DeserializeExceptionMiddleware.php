<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use AzureOss\Storage\Common\Exceptions\RequestExceptionDeserializer;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
final class DeserializeExceptionMiddleware
{
    public function __construct(private RequestExceptionDeserializer $exceptionDeserializer) {}

    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            /** @phpstan-ignore-next-line */
            return $handler($request, $options)
                ->otherwise(function (\Throwable $e) {
                    if ($e instanceof RequestException) {
                        throw $this->exceptionDeserializer->deserialize($e);
                    }

                    throw $e;
                });
        };
    }
}
