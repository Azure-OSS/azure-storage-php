<?php

declare(strict_types=1);

namespace AzureOss\Storage\Tests\Utils;

use GuzzleHttp\Psr7\Utils;

final class FileFactory
{
    public static function create(int $size): string
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('azure-oss', true);

        $resource = Utils::streamFor(Utils::tryFopen($path, 'w'));

        $chunk = 1000;
        while ($size > 0) {
            $chunkContent = str_pad('', min($chunk, $size));
            $resource->write($chunkContent);
            $size -= $chunk;
        }
        $resource->close();

        return $path;
    }

    public static function withStream(int $size, callable $callable): void
    {
        $path = FileFactory::create($size);
        $stream = Utils::streamFor(Utils::tryFopen($path, 'r'));

        try {
            $callable($stream);
        } finally {
            unlink($path);
        }
    }

    public static function clean(): void
    {
        $files = glob(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'azure-oss*');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
