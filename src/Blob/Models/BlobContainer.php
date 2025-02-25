<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

final class BlobContainer
{
    /**
     * @deprecated will be private in version 2
     */
    public function __construct(
        public readonly string $name,
        public readonly BlobContainerProperties $properties,
    ) {}

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        return new self(
            (string) $xml->Name,
            BlobContainerProperties::fromXml($xml->Properties),
        );
    }
}
