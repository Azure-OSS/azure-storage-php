<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

class Blob
{
    public string $name;

    public BlobProperties $properties;
}
