<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob;

use AzureOss\Storage\Blob\Exceptions\InvalidBlobUriException;
use Psr\Http\Message\UriInterface;

class BlobUriParser
{
    public static function getContainerName(UriInterface $uri): string
    {
        $segments = self::getPathSegments($uri);

        if (self::isDevelopmentUri($uri)) {
            array_shift($segments);
        }

        if (count($segments) === 0) {
            throw new InvalidBlobUriException();
        }

        return $segments[0];
    }

    public static function getBlobName(UriInterface $uri): string
    {
        $segments = self::getPathSegments($uri);

        if (self::isDevelopmentUri($uri)) {
            array_shift($segments);
        }

        array_shift($segments);

        if (count($segments) === 0) {
            throw new InvalidBlobUriException();
        }

        return implode('/', $segments);
    }

    public static function isDevelopmentUri(UriInterface $uri): bool
    {
        return ! str_contains($uri->getHost(), "blob.core.windows.net");
    }

    /**
     * @return string[]
     */
    private static function getPathSegments(UriInterface $uri): array
    {
        return array_values(array_filter(explode("/", $uri->getPath())));
    }
}
