<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Helpers;

use GuzzleHttp\Psr7\Utils as StreamUtils;
use Psr\Http\Message\StreamInterface;

/**
 * @internal
 */
final class StreamHelper
{
    /**
     * @param  string|resource|StreamInterface  $content
     */
    public static function createUploadStream($content, int $blockSize): StreamInterface
    {
        if (is_string($content)) {
            return self::createUploadStreamFromString($content);
        }

        if ($content instanceof StreamInterface) {
            return self::createUploadStreamFromStream($content, $blockSize);
        }

        return self::createUploadStreamFromResource($content, $blockSize);
    }

    private static function createUploadStreamFromString(string $content): StreamInterface
    {
        return StreamUtils::streamFor($content);
    }

    private static function createUploadStreamFromStream(StreamInterface $content, int $blockSize): StreamInterface
    {
        $detached = $content->detach();
        if ($detached === null) {
            return $content;
        }

        return self::createUploadStreamFromResource($detached, $blockSize);
    }

    /**
     * @param  resource  $content
     */
    private static function createUploadStreamFromResource($content, int $blockSize): StreamInterface
    {
        stream_set_chunk_size($content, $blockSize);

        return StreamUtils::streamFor($content);
    }
}
