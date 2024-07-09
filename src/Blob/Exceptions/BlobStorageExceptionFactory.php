<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Exceptions;

use AzureOss\Storage\Blob\Responses\ErrorResponse;
use GuzzleHttp\Exception\RequestException;
use JMS\Serializer\SerializerInterface;

/**
 * @internal
 */
final class BlobStorageExceptionFactory
{
    public function __construct(
        private readonly SerializerInterface $serializer,
    ) {}

    public function create(RequestException $e): \Exception
    {
        $error = $this->getErrorResponse($e);

        if ($error === null) {
            return $e;
        }

        return match ($error->code) {
            'AuthorizationFailure' => new AuthorizationFailedException($error->message, previous: $e),
            'ContainerNotFound' => new ContainerNotFoundException($error->message, previous: $e),
            'ContainerAlreadyExists' => new ContainerAlreadyExistsException($error->message, previous: $e),
            'BlobNotFound' => new BlobNotFoundException($error->message, previous: $e),
            'InvalidBlockList' => new InvalidBlockListException($error->message, previous: $e),
            'TagsTooLarge' => new TagsTooLargeException($error->message, previous: $e),
            default => new BlobStorageException($error->message, previous: $e),
        };
    }

    public function getErrorResponse(RequestException $e): ?ErrorResponse
    {
        $response = $e->getResponse();
        if ($response === null) {
            throw $e;
        }

        $content = $response->getBody()->getContents();
        if ($content !== "") {
            try {
                /** @phpstan-ignore-next-line */
                return $this->serializer->deserialize($content, ErrorResponse::class, 'xml');
            } catch (\Exception) {
                return null;
            }
        }

        $code = $response->getHeaderLine("x-ms-error-code");
        if ($code !== "") {
            return new ErrorResponse($code, $response->getHeaderLine("x-ms-request-id"));
        }

        return null;
    }
}
