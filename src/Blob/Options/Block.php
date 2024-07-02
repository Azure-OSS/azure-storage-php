<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Options;

class Block
{
    public function __construct(
        public string $id,
        public BlockType $type,
    ) {
    }
}
