<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

class Blob implements XmlDecodable
{
    private function __construct(
        public string $name,
        public BlobProperties $properties,
    ) {
    }

    public static function fromXml(array $parsed): static
    {
        $name = $parsed['Name'];
        $properties = BlobProperties::fromXml($parsed['Properties']);

        return new self($name, $properties);
    }
}
