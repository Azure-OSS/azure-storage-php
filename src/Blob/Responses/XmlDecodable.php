<?php

namespace AzureOss\Storage\Blob\Responses;

interface XmlDecodable
{
    /**
     * @param array<string, mixed> $parsed
     */
    public static function fromXml(array $parsed): static;
}
