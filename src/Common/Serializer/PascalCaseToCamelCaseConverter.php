<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Serializer;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class PascalCaseToCamelCaseConverter implements NameConverterInterface
{
    public function normalize(string $propertyName): string
    {
        return ucfirst($propertyName);
    }

    public function denormalize(string $propertyName): string
    {
        return lcfirst($propertyName);
    }
}
