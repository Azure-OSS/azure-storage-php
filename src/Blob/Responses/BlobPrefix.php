<?php

namespace AzureOss\Storage\Blob\Responses;

class BlobPrefix
{
    public function __construct(
        public string $name
    )
    {
    }

    public static function fromXml(array $array): self
    {
        $name = $array['Name'];

        return new self($name);
    }
}
