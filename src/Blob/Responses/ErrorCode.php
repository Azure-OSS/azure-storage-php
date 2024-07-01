<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use GuzzleHttp\Exception\RequestException;

enum ErrorCode: string
{
    case AUTHORIZATION_FAILURE = 'AuthorizationFailure';
    case CONTAINER_NOT_FOUND = 'ContainerNotFound';
    case CONTAINER_ALREADY_EXISTS = 'ContainerAlreadyExists';
    case BLOB_NOT_FOUND = 'BlobNotFound';
    case INVALID_BLOCK_LIST = 'InvalidBlockList';

    public static function fromRequestException(RequestException $e): ?self
    {
        $code = $e->getResponse()?->getHeader('x-ms-error-code')[0] ?? null;

        if ($code === null) {
            return null;
        }

        return self::tryFrom($code);
    }
}
