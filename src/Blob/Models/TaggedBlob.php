<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

use AzureOss\Storage\Blob\Requests\Tag;

final class TaggedBlob
{
    /**
     * @param Tag[] $tags
     */
    public function __construct(
        public readonly string $name,
        public readonly string $containerName,
        public readonly array $tags,
    ) {}
}
