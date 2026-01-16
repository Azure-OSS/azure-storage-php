<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

/**
 * @internal
 */
final class ErrorResponse
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        return new self(
            (string) $xml->Code,
            (string) $xml->Message,
        );
    }
}
