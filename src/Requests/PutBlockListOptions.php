<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Requests;

 class PutBlockListOptions
{
    public function __construct(
        public  ?string $contentType = null
    ) {
    }
}
