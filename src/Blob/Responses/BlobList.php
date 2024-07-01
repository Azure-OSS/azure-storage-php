<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use Symfony\Component\Serializer\Annotation\SerializedName;

class BlobList
{
    /**
     * @param  Blob[]  $items
     */
    public function __construct(
        #[SerializedName('Blob')]
        public array $items = []
    ) {
    }
}
