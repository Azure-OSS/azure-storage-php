<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Requests;

 class Block
{
    public function __construct(
        public string $id,
        public BlockType $type,
    ) {
    }
}
