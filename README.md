# Azure Storage PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/azure-oss/storage.svg)](https://packagist.org/packages/azure-oss/storage)
[![Packagist Downloads](https://img.shields.io/packagist/dt/azure-oss/storage)](https://packagist.org/packages/azure-oss/storage)

Community-driven PHP SDKs for Azure, because Microsoft won't.

In November 2023, Microsoft officially archived their [Azure SDK for PHP](https://github.com/Azure/azure-sdk-for-php) and stopped maintaining PHP integrations for most Azure services. No migration path, no replacement — just a repository marked read-only.

We picked up where they left off.

<img src="https://azure-oss.github.io/img/logo.svg" width="150" alt="Screenshot">

Our other packages:

- **[azure-oss/storage-blob-flysystem](https://packagist.org/packages/azure-oss/storage-blob-flysystem)** – Flysystem adapter  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-blob-flysystem)

- **[azure-oss/storage-blob-laravel](https://packagist.org/packages/azure-oss/storage-blob-laravel)** – Laravel filesystem driver  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-blob-laravel)

- **[azure-oss/storage-queue](https://packagist.org/packages/azure-oss/storage-queue)** – Azure Storage Queue SDK  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-queue)

- **[azure-oss/storage-queue-laravel](https://packagist.org/packages/azure-oss/storage-queue-laravel)** – Laravel Queue connector  
  ![Downloads](https://img.shields.io/packagist/dt/azure-oss/storage-queue-laravel)

## Features
- Authentication:
  - Connection strings (access keys)
  - Shared key credentials
  - Shared access signatures (SAS) for delegated, time-limited access
  - Microsoft Entra ID (token-based authentication) via azure-oss/azure-identity
- Local development:
  - Supports the Azurite emulator
- Containers:
  - Create, delete, and list (including filtering by prefix)
  - Configure public access when creating a container
  - Read properties and manage metadata
- Blobs:
  - Upload from strings or streams, with transfer tuning for large uploads
  - Set common HTTP headers (content type, cache control, etc.)
  - Download via streaming and access response properties
  - Copy blobs (synchronous and asynchronous)
  - List blobs (flat, by prefix, and hierarchical listing) with page sizing
  - Delete blobs
  - Read properties and manage metadata
  - Blob index tags: set/get tags and query blobs by tags (account or container scope)
- SAS:
  - Generate SAS for blobs, containers, and the account (when using credentials that can sign SAS)

## Documentation

You can read the documentation [here](https://azure-oss.github.io).

## Install

```shell
composer require azure-oss/storage
```

## Quickstart

```php
<?php

use AzureOss\Storage\Blob\BlobServiceClient;
use AzureOss\Storage\Blob\Models\UploadBlobOptions;

$service = BlobServiceClient::fromConnectionString(
    getenv('AZURE_STORAGE_CONNECTION_STRING')
);

$container = $service->getContainerClient('quickstart');
$container->createIfNotExists();

$blob = $container->getBlobClient('hello.txt');

$blob->upload(
    'Hello from Azure-OSS',
    new UploadBlobOptions(contentType: 'text/plain')
);

$download = $blob->downloadStreaming();
$content = $download->content->getContents();

echo $content.PHP_EOL; // Hello from Azure-OSS

foreach ($container->getBlobs() as $item) {
    echo $item->name.PHP_EOL;
}

// Optional cleanup
$blob->deleteIfExists();
// $container->deleteIfExists();
```

## License

This project is released under the MIT License. See [LICENSE](https://github.com/Azure-OSS/azure-storage-php-monorepo/blob/02759360186be8d2d04bd1e9b2aba3839b6d39dc/LICENSE) for details.
