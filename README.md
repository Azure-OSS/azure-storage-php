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

### Authentication Options

#### Connection String (traditional)
```php
$blobServiceClient = BlobServiceClient::fromConnectionString("<connection string>");
```

#### Azure Workload Identity (Kubernetes/recommended)
```php
// For Kubernetes pods with Workload Identity configured
$blobServiceClient = BlobServiceClient::fromWorkloadIdentity('mystorageaccount');
```

#### Azure Managed Identity (alias for Workload Identity)
```php
// Alternative method name for the same functionality
$blobServiceClient = BlobServiceClient::fromManagedIdentity('mystorageaccount');
```

### Basic Operations

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

// If the container is not public, you will have to create a SAS uri with read rights
$targetBlobClient = $containerClient->getBlobClient("target.txt");
$targetBlobClient->syncCopyFromUri($sourceBlobClient->uri);
```
Copy a blob with asynchronous scheduling
```php
$copyResult = $targetBlobClient->startCopyFromUri(new Uri(...));

if ($targetBlobClient->getProperties()->copyStatus === CopyStatus::PENDING) {
    $targetBlobClient->abortCopyFromUri($copyResult->copyId);
}
```

Generate a container [Service SAS](https://learn.microsoft.com/en-us/azure/storage/common/storage-sas-overview#service-sas)
```php
$sas = $containerClient->generateSasUri(
    BlobSasBuilder::new()
        ->setPermissions(new BlobSasPermissions(read: true))
        ->setExpiresOn((new \DateTime())->modify("+ 15min")),
);
```

Use a container [Service SAS](https://learn.microsoft.com/en-us/azure/storage/common/storage-sas-overview#service-sas)
```php
$sas = new Uri("https://azure-oss.blob.core.windows.net/quickstart?sp=...&st=...&se=...&spr=...&sv=...sr=...&sig=...")
$containerClient = new BlobContainerClient($sas);
```

Generate a blob [Service SAS](https://learn.microsoft.com/en-us/azure/storage/common/storage-sas-overview#service-sas)
```php
$sas = $blobClient->generateSasUri(
    BlobSasBuilder::new()
        ->setPermissions(new BlobSasPermissions(read: true))
        ->setExpiresOn((new \DateTime())->modify("+ 15min")),
);
```

Use a blob [Service SAS](https://learn.microsoft.com/en-us/azure/storage/common/storage-sas-overview#service-sas)
```php
$sas = new Uri("https://azure-oss.blob.core.windows.net/quickstart/file.txt?sp=...&st=...&se=...&spr=...&sv=...sr=...&sig=...")
$blobClient = new BlobClient($sas);
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

Create a public container
```php
$containerClient = $blobServiceClient->getContainerClient('quickstart-public');

$containerClient->create(
    new CreateContainerOptions(publicAccessType: PublicAccessType::BLOB)
);
```

Use a public container
```php
$publicUri = new Uri("https://azure-oss.blob.core.windows.net/quickstart-public/file.txt");

$blobClient = new BlobClient($publicUri);
```

Delete a container
```php
$containerClient->delete();
```

## Azure Workload Identity Setup

For Kubernetes environments, Azure Workload Identity allows your pods to authenticate to Azure services without storing credentials.

### Prerequisites

1. **Kubernetes cluster** with Workload Identity enabled
2. **Azure AD application** configured for Workload Identity
3. **Environment variables** injected by Workload Identity:
   - `AZURE_TENANT_ID`
   - `AZURE_CLIENT_ID`
   - `AZURE_FEDERATED_TOKEN_FILE`

### Kubernetes Configuration Example

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: my-app
spec:
  template:
    metadata:
      labels:
        azure.workload.identity/use: "true"
    spec:
      serviceAccountName: my-service-account
      containers:
      - name: app
        image: my-app:latest
        env:
        - name: AZURE_STORAGE_ACCOUNT
          value: "mystorageaccount"
---
apiVersion: v1
kind: ServiceAccount
metadata:
  name: my-service-account
  annotations:
    azure.workload.identity/client-id: "<your-client-id>"
```

### PHP Usage

```php
<?php
// The SDK automatically reads environment variables
$blobServiceClient = BlobServiceClient::fromWorkloadIdentity($_ENV['AZURE_STORAGE_ACCOUNT']);

// Use the client normally
$containerClient = $blobServiceClient->getContainerClient('my-container');
$containerClient->createIfNotExists();

$blobClient = $containerClient->getBlobClient('hello.txt');
$blobClient->upload('Hello from Workload Identity!');
```

### Authentication Comparison

| Method | Use Case | Security | Setup Complexity |
|--------|----------|----------|------------------|
| Connection String | Development, simple scenarios | Store secrets | Low |
| Workload Identity | Kubernetes production | No stored secrets | Medium |
| SAS Tokens | Temporary access | Time-limited | Low |

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
