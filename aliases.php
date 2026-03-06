<?php

declare(strict_types=1);

use AzureOss\Storage\Blob\Models\BlobClientOptions;
use AzureOss\Storage\Blob\Models\BlobContainerClientOptions;
use AzureOss\Storage\Blob\Models\BlobServiceClientOptions;
use AzureOss\Storage\Blob\Models\BlockBlobClientOptions;

if (! class_exists('AzureOss\\Storage\\Blob\\Options\\BlobClientOptions', false) && class_exists(BlobClientOptions::class)) {
    class_alias(BlobClientOptions::class, 'AzureOss\\Storage\\Blob\\Options\\BlobClientOptions');
}

if (! class_exists('AzureOss\\Storage\\Blob\\Options\\BlobContainerClientOptions', false) && class_exists(BlobContainerClientOptions::class)) {
    class_alias(BlobContainerClientOptions::class, 'AzureOss\\Storage\\Blob\\Options\\BlobContainerClientOptions');
}

if (! class_exists('AzureOss\\Storage\\Blob\\Options\\BlobServiceClientOptions', false) && class_exists(BlobServiceClientOptions::class)) {
    class_alias(BlobServiceClientOptions::class, 'AzureOss\\Storage\\Blob\\Options\\BlobServiceClientOptions');
}

if (! class_exists('AzureOss\\Storage\\Blob\\Options\\BlockBlobClientOptions', false) && class_exists(BlockBlobClientOptions::class)) {
    class_alias(BlockBlobClientOptions::class, 'AzureOss\\Storage\\Blob\\Options\\BlockBlobClientOptions');
}

