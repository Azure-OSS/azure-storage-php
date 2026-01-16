<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Helpers;

/**
 * @internal
 */
final class HashHelper
{
    public static function serializeMd5(string $md5): string
    {
        return base64_encode($md5);
    }

    public static function deserializeMd5(string $content): ?string
    {
        $result = base64_decode($content, true);
        if ($result === false) {
            return null;
        }

        return bin2hex($result);
    }
}
