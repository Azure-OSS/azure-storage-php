<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\XmlList;

class ListBlobsResponse
{
    public string $prefix;

    public string $marker;

    public int $maxResults;

    public string $delimiter;

    /**
     * @var Blob[]
     */
    #[XmlList(entry: "Blob")]
    public array $blobs;

    /**
     * @var BlobPrefix[]
     */
    #[SerializedName("Blobs")]
    #[XmlList(entry: "BlobPrefix")]
    public array $blobPrefixes;
}
