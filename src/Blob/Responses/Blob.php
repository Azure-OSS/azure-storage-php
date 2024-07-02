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
        $name = Xml::str($parsed, 'Name');
        $properties = BlobProperties::fromXml(Xml::assoc($parsed, 'Properties'));

        return new self($name, $properties);
    }
}
