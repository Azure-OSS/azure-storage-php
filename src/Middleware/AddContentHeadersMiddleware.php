<?php

declare(strict_types=1);

namespace AzureOss\Storage\Middleware;

use Psr\Http\Message\RequestInterface;

class AddContentHeadersMiddleware
{
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $content = $request->getBody()->getContents();
            $contentLength = strlen($content);

            if ($contentLength > 0) {
                $request = $request
                    ->withHeader('Content-Length', (string) $contentLength)
                    ->withHeader('Content-MD5', base64_encode(md5($content, true)));
            }

            return $handler($request, $options);
        };
    }
}
