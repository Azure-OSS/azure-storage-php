# Azure Storage PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/azure-oss/storage.svg)](https://packagist.org/packages/azure-oss/storage)
[![Packagist Downloads](https://img.shields.io/packagist/dm/azure-oss/storage)](https://packagist.org/packages/azure-oss/storage)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/azure-oss/azure-storage-php/tests.yml?branch=main)](https://github.com/azure-oss/azure-storage-php/actions)

> [!TIP]
> If you’re working with Laravel or Flysystem, check out our dedicated drivers.
> * [Azure Storage Laravel Adapter](https://github.com/Azure-OSS/azure-storage-php-adapter-laravel)
> * [Azure Storage Flysystem Adapter](https://github.com/Azure-OSS/azure-storage-php-adapter-flysystem)

## Minimum Requirements

* PHP 8.1 or above
* Required PHP extensions
    * curl
    * json

## Install

```shell
composer require azure-oss/storage
```

## Quickstart

Create the BlobServiceClient
```php
$blobServiceClient = BlobServiceClient::fromConnectionString("<connection string>");
```

Create a container
```php
$containerClient = $blobServiceClient->getContainerClient('quickstart');
$containerClient->create();
```

List containers in a storage account
```php
$containers = $blobServiceClient->getBlobContainers();
```

Upload a blob to a container
```php
$blobClient = $containerClient->getBlobClient("hello.txt");
$blobClient->upload("world!");

// or using streams
$file = fopen('hugefile.txt', 'r');
$blobClient->upload($file);
```

List blobs in a container
```php
$blobs = $containerClient->getBlobs();

// or with a prefix
$blobs = $containerClient->getBlobs('some/virtual/directory');

// and if you want both the blobs and virtual directories
$blobs = $containerClient->getBlobsByHierarchy('some/virtual/directory');
```

Download a blob
```php
$result = $blobClient->downloadStreaming();

$props = $result->properties;
$content = $result->content->getContents();
```

Copy a blob
```php
$sourceBlobClient = $containerClient->getBlobClient("source.txt");

// Can also be a different container
$targetBlobClient = $containerClient->getBlobClient("target.txt");
$targetBlobClient->copyFromUri($sourceBlobClient->uri);
```

Generate and use a [Service SAS](https://learn.microsoft.com/en-us/azure/storage/common/storage-sas-overview#service-sas)
```php
$sas = $blobClient->generateSasUri(
    BlobSasBuilder::new()
        ->setPermissions(new BlobSasPermissions(read: true))
        ->setExpiresOn((new \DateTime())->modify("+ 15min")),
);

$sasBlobClient = new BlobClient($sas);
```

Generate and use an [Account SAS](https://learn.microsoft.com/en-us/azure/storage/common/storage-sas-overview#account-sas)
```php
$sas = $blobServiceClient->generateAccountSasUri(
    AccountSasBuilder::new()
        ->setPermissions(new AccountSasPermissions(list: true))
        ->setResourceTypes(new AccountSasResourceTypes(service: true))
        ->setExpiresOn((new \DateTime())->modify("+ 15min")),
);

$sasServiceClient = new BlobServiceClient($sas);
```

Delete a container
```php
$containerClient->delete();
```

## Documentation

For more information visit the documentation at [azure-oss.github.io](https://azure-oss.github.io).

## Support

Do you need help, do you want to talk to us, or is there anything else?

Join us at:

* [Github Discussions](https://github.com/Azure-OSS/azure-storage-php/discussions)
* [Slack](https://join.slack.com/t/azure-oss/shared_invite/zt-2lw5knpon-mqPM_LIuRZUoH02AY8uiYw)

## License

Azure-Storage-PHP is released under the MIT License. See [LICENSE](./LICENSE) for details.

## PHP Version Support Policy

The maintainers of this package add support for a PHP version following its initial release and drop support for a PHP version once it has reached its end of security support.

## Backward compatibility promise

Azure-Storage-PHP is using Semver. This means that versions are tagged with MAJOR.MINOR.PATCH. Only a new major version will be allowed to break backward compatibility (BC).

Classes marked as @experimental or @internal are not included in our backward compatibility promise. You are also not guaranteed that the value returned from a method is always the same. You are guaranteed that the data type will not change.

PHP 8 introduced named arguments, which increased the cost and reduces flexibility for package maintainers. The names of the arguments for methods in the library are not included in our BC promise.
