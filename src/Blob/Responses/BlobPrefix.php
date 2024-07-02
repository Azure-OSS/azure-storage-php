<?php

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Common\Utils\Xml;

final class BlobPrefix implements XmlDecodable
{
    public function __construct(
        public string $name
    )
    {
    }

    public static function fromXml(array $parsed): static
    {
        $name = Xml::str($parsed, 'Name');

        return new self($name);
    }
}
