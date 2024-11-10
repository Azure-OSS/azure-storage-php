<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

/**
 * @internal
 */
final class Block
{
    public function __construct(
        public int $number,
        public BlockType $type,
    ) {}

    public function getId(): string
    {
        return base64_encode(str_pad((string) $this->number, 6, '0', STR_PAD_LEFT));
    }
}
