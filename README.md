# Azure Storage PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/azure-oss/storage.svg)](https://packagist.org/packages/azure-oss/storage)
[![Packagist Downloads](https://img.shields.io/packagist/dt/azure-oss/storage)](https://packagist.org/packages/azure-oss/storage)

In November 2023, Microsoft officially archived their [Azure SDK for PHP](https://github.com/Azure/azure-sdk-for-php) and stopped maintaining PHP integrations for most Azure services. No migration path, no replacement — just a repository marked read-only.

We picked up where they left off.

<img src="https://azure-oss.github.io/img/logo.svg" width="150" alt="Screenshot">

**azure-oss/storage** is a metapackage that bundles the community-driven PHP SDKs for Azure Storage.

Currently, it includes:
- [**azure-oss/storage-blob**](https://github.com/Azure-OSS/azure-storage-blob-php) – Azure Blob Storage SDK
- [**azure-oss/storage-queue**](https://github.com/Azure-OSS/azure-storage-queue-php) – Azure Storage Queue SDK

## Documentation

Full documentation can be found [here](https://azure-oss.github.io).

## Installation

```shell
composer require azure-oss/storage
```

## Upgrade

Please refer to the [UPGRADE.md](UPGRADE.md) for instructions on upgrading from v1 to v2.

## License

This project is released under the MIT License. See [LICENSE](LICENSE) for details.
