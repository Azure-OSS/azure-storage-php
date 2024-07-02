<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Serializer;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;

class SerializerFactory
{
    public function create(): SerializerInterface
    {
        return SerializerBuilder::create()
            ->setPropertyNamingStrategy(new PascalCaseToCamelCaseNamingStrategy())
            ->setDocBlockTypeResolver(true)
            ->addDefaultHandlers()
            ->build();
    }
}
