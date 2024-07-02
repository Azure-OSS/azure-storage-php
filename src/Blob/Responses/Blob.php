<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Common\Utils\Xml;

final class Blob implements XmlDecodable
{
    private function __construct(
        public string $name,
        public BlobProperties $properties,
    ) {
    }

    public static function fromXml(array $parsed): static
    {
        return new self(
            Xml::str($parsed, 'Name'),
            BlobProperties::fromXml(Xml::assoc($parsed, 'Properties'))
        );
    }
}
