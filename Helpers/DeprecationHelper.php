<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Helpers;

class DeprecationHelper
{
    public static function constructorWillBePrivate(string $className, string $version): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $wasCalledFromOutside = isset($backtrace[2]['class']) && $backtrace[2]['class'] !== $className;

        if ($wasCalledFromOutside) {
            @trigger_error(sprintf('The constructor of %s will be private in version %s.', $className, $version), E_USER_DEPRECATED);
        }
    }

    public static function methodWillBeRemoved(string $className, string $methodName, string $version): void
    {
        @trigger_error(sprintf('The method %s::%s() will be removed in version %s.', $className, $methodName, $version), E_USER_DEPRECATED);
    }
}
