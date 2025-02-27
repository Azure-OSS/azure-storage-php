<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Helpers;

class DeprecationHelper
{
    public static function constructorWillBePrivate(string $className, string $version): void
    {
        if (isset(debug_backtrace()[2]['class']) && debug_backtrace()[2]['class'] !== $className) {
            @trigger_error(sprintf('The constructor of %s will be private in version %s.', $className, $version), E_USER_DEPRECATED);
        }
    }
}
