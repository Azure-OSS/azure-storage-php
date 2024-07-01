<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Responses;

 class Blob
{
    public function __construct(
        public string $name,
        public BlobProperties $properties,
    ) {
    }
}
