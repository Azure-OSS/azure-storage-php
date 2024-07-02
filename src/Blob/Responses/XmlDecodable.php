<?php

namespace AzureOss\Storage\Blob\Responses;

interface XmlDecodable
{
    public static function fromXml(array $parsed): static;
}
