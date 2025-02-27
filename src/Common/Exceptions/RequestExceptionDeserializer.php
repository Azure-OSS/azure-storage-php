<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Exceptions;

use GuzzleHttp\Exception\RequestException;

/**
 * @internal
 */
interface RequestExceptionDeserializer
{
    public function deserialize(RequestException $exception): \Exception;
}
