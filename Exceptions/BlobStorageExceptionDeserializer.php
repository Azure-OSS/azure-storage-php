<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Exceptions;

use AzureOss\Storage\Blob\Responses\ErrorResponse;
use AzureOss\Storage\Common\Exceptions\RequestExceptionDeserializer;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
final class BlobStorageExceptionDeserializer implements RequestExceptionDeserializer
{
    public function deserialize(RequestException $e): \Exception
    {
        $response = $e->getResponse();
        if ($response === null) {
            return $e;
        }

        $error = $this->getErrorFromResponseBody($response) ?? $this->getErrorResponseFromHeaders($response);
        if ($error === null) {
            return $e;
        }

        return match ($error->code) {
            'AuthenticationFailed' => new AuthenticationFailedException($error->message, previous: $e),
            'AuthorizationFailure' => new AuthorizationFailedException($error->message, previous: $e),
            'ContainerNotFound' => new ContainerNotFoundException($error->message, previous: $e),
            'ContainerAlreadyExists' => new ContainerAlreadyExistsException($error->message, previous: $e),
            'BlobNotFound' => new BlobNotFoundException($error->message, previous: $e),
            'InvalidBlockList' => new InvalidBlockListException($error->message, previous: $e),
            'TagsTooLarge' => new TagsTooLargeException($error->message, previous: $e),
            'CannotVerifyCopySource' => new CannotVerifyCopySourceException($error->message, previous: $e),
            'NoPendingCopyOperation' => new NoPendingCopyOperationException($error->message, previous: $e),
            default => new BlobStorageException($error->message, previous: $e),
        };
    }

    public function getErrorResponseFromHeaders(ResponseInterface $response): ?ErrorResponse
    {
        $code = $response->getHeaderLine('x-ms-error-code');
        if ($code === '') {
            return null;
        }

        return new ErrorResponse($code, $response->getHeaderLine('x-ms-request-id'));
    }

    private function getErrorFromResponseBody(ResponseInterface $response): ?ErrorResponse
    {
        $content = $response->getBody()->getContents();
        if ($content === '') {
            return null;
        }

        return ErrorResponse::fromXml(new \SimpleXMLElement($content));
    }
}
