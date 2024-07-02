<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\XmlList;

class ListBlobsResponse
{
    public readonly string $prefix;

    public readonly string $marker;

    public readonly int $maxResults;

    public readonly string $delimiter;

    /**
     * @var Blob[]
     */
    #[XmlList(entry: "Blob")]
    public readonly array $blobs;

    /**
     * @var BlobPrefix[]
     */
    #[SerializedName("Blobs")]
    #[XmlList(entry: "BlobPrefix")]
    public readonly array $blobPrefixes;
}
