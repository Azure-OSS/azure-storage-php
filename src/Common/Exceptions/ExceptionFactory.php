<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Exceptions;

use AzureOss\Storage\Blob\Exceptions\AuthorizationFailedException;
use AzureOss\Storage\Blob\Exceptions\BlobNotFoundException;
use AzureOss\Storage\Blob\Exceptions\ContainerAlreadyExistsException;
use AzureOss\Storage\Blob\Exceptions\ContainerNotFoundException;
use AzureOss\Storage\Blob\Exceptions\InvalidBlockListException;
use AzureOss\Storage\Blob\Responses\ErrorCode;
use GuzzleHttp\Exception\RequestException;

final class ExceptionFactory
{
    public function create(RequestException $e): \Exception
    {
        return match (ErrorCode::fromRequestException($e)) {
            ErrorCode::AUTHORIZATION_FAILURE => new AuthorizationFailedException($e),
            ErrorCode::CONTAINER_NOT_FOUND => new ContainerNotFoundException($e),
            ErrorCode::CONTAINER_ALREADY_EXISTS => new ContainerAlreadyExistsException($e),
            ErrorCode::BLOB_NOT_FOUND => new BlobNotFoundException($e),
            ErrorCode::INVALID_BLOCK_LIST => new InvalidBlockListException($e),
            default => $e,
        };
    }
}
