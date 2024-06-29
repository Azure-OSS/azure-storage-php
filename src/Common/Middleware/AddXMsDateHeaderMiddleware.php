<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use Psr\Http\Message\RequestInterface;

final class AddXMsDateHeaderMiddleware
{
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $date = new \DateTime("now", new \DateTimeZone('UTC'));

            $request = $request->withHeader("x-ms-date", $date->format('D, d M Y H:i:s T'));

            return $handler($request, $options);
        };
    }
}
