<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Responses;

use AzureOss\Storage\Common\Utils\Xml;

final class BlobPrefix implements XmlDecodable
{
    public function __construct(
        public string $name
    ) {
    }

    public static function fromXml(array $parsed): static
    {
        return new self(Xml::str($parsed, 'Name'));
    }
}
