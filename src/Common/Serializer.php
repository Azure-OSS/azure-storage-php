<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common;

use AzureOss\Storage\Common\Serializer\PascalCaseToCamelCaseConverter;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

class Serializer
{
    public static function create(): SymfonySerializer
    {
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory, new PascalCaseToCamelCaseConverter());
        $phpDocExtractor = new PhpDocExtractor();

        $normalizers = [
            new ArrayDenormalizer(),
            new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter, null, $phpDocExtractor, ),
        ];

        $encoders = [
            new XmlEncoder(),
        ];

        return new SymfonySerializer($normalizers, $encoders);
    }
}
