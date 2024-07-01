<?php

declare(strict_types=1);

namespace AzureOss\Storage\Responses;

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
