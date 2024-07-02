<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Serializer;

use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;

class PascalCaseToCamelCaseNamingStrategy implements PropertyNamingStrategyInterface
{
    public function translateName(PropertyMetadata $property): string
    {
        return ucfirst($property->name);
    }
}
