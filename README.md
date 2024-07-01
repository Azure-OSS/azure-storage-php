# Azure Storage PHP

## Minimum Requirements

* PHP 8.1 or above
* Required PHP extensions
    * curl
    * json
  
## Install

```shell
composer require azure-oss/azure-storage-php
```

## Documentation

For more information visit the documentation at [azure-oss.github.io](https://azure-oss.github.io).

## License

Azure-Storage-PHP is released under the MIT License. See [LICENSE](./LICENSE.md) for details.

## PHP Version Support Policy

The maintainers of this package add support for a PHP version following its initial release and drop support for a PHP version once it has reached its end of security support.

## Backward compatibility promise

Azure-Storage-PHP is using Semver. This means that versions are tagged with MAJOR.MINOR.PATCH. Only a new major version will be allowed to break backward compatibility (BC).

Classes marked as @experimental or @internal are not included in our backward compatibility promise. You are also not guaranteed that the value returned from a method is always the same. You are guaranteed that the data type will not change.

PHP 8 introduced named arguments, which increased the cost and reduces flexibility for package maintainers. The names of the arguments for methods in the library are not included in our BC promise.
