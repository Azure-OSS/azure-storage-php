<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Utils;

use GuzzleHttp\Psr7\Utils;

final class FileFactory
{
    public static function withStream(int $size, callable $callable): void
    {
        $path = sys_get_temp_dir() . '/azure-oss-test-file';

        unlink($path);
        $resource = Utils::streamFor(Utils::tryFopen($path, 'w'));

        $chunk = 1000;
        while ($size > 0) {
            $chunkContent = str_pad('', min($chunk, $size));
            $resource->write($chunkContent);
            $size -= $chunk;
        }
        $resource->close();

        $callable(Utils::streamFor(Utils::tryFopen($path, 'r')));

        unlink($path);
    }
}
