# Upgrade Guide

## Upgrade from v1 to v2

In version 2, `azure-oss/storage` was converted into a **metapackage**. 

### Key Changes
- All Blob Storage related code was moved to its own package: [`azure-oss/storage-blob`](https://github.com/Azure-OSS/azure-storage-blob-php).
- The `azure-oss/storage` package now simply requires `azure-oss/storage-blob` and `azure-oss/storage-queue`.
- Namespace and class names remain unchanged if you were already using `AzureOss\Storage\Blob\...`.

### How to Upgrade
1. Update your `composer.json` to require `^2.0`:
   ```shell
   composer require azure-oss/storage:^2.0
   ```
2. **Important:** Read the [release notes of azure-oss/storage-blob v2.0.0](https://github.com/Azure-OSS/azure-storage-blob-php/releases/tag/2.0.0) for specific changes related to the Blob storage implementation.
