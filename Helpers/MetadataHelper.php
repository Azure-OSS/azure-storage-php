<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Helpers;

/**
 * @internal
 */
final class MetadataHelper
{
    /**
     * @param  array<string>  $metadata
     * @return array<string>
     */
    public static function metadataToHeaders(array $metadata): array
    {
        $headers = [];

        foreach ($metadata as $key => $value) {
            $headers["x-ms-meta-$key"] = $value;
        }

        return $headers;
    }

    /**
     * @param  string[][]  $headers
     * @return array<string>
     */
    public static function headersToMetadata(array $headers): array
    {
        $metadata = [];

        foreach ($headers as $key => $value) {
            if (str_starts_with($key, 'x-ms-meta-')) {
                $metadata[substr($key, strlen('x-ms-meta-'))] = implode(', ', $value);
            }
        }

        return $metadata;
    }
}
