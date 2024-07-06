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

        return match ($error?->code) {
            'AuthorizationFailure' => new AuthorizationFailedExceptionBlob($error->message, previous: $e),
            'ContainerNotFound' => new ContainerNotFoundExceptionBlob($error->message, previous: $e),
            'ContainerAlreadyExists' => new ContainerAlreadyExistsExceptionBlob($error->message, previous: $e),
            'BlobNotFound' => new BlobNotFoundExceptionBlob($error->message, previous: $e),
            'InvalidBlockList' => new InvalidBlockListException($error->message, previous: $e),
            default => $e,
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
