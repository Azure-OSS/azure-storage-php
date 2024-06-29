<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Middleware;

use Psr\Http\Message\RequestInterface;

class AddContentHeadersMiddleware
{
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $contentLength = strlen((string) $request->getBody());

            if($contentLength > 0) {
                $request = $request
                    ->withHeader('Content-Length', $contentLength)
                    ->withHeader('Content-MD5', base64_encode(md5((string) $request->getBody(), true)));
            }

            return $handler($request, $options);
        };
    }
}
