<?php

namespace AzureOss\Storage\Common\Utils;

use AzureOss\Storage\Common\Exceptions\InvalidXmlException;

class Xml
{
    /**
     * @param array<string, mixed> $parsed
     * @throws InvalidXmlException
     */
    public static function str(array $parsed, string $key): string
    {
        $value = self::value($parsed, $key);

        if (!is_scalar($value)) {
            throw new InvalidXmlException();
        }

        return (string)$value;
    }

    /**
     * @param array<string, mixed> $parsed
     * @throws InvalidXmlException
     */
    public static function dateTime(array $parsed, string $key, string $format = \DateTimeInterface::ATOM): \DateTimeInterface
    {
        $date = \DateTimeImmutable::createFromFormat($format, self::str($parsed, $key));

        if ($date === false) {
            throw new InvalidXmlException();
        }

        return $date;
    }

    /**
     * @param array<string, mixed> $parsed
     * @throws InvalidXmlException
     */
    public static function int(array $parsed, string $key): int
    {
        $value = self::value($parsed, $key);

        if(! is_numeric($value)) {
            throw new InvalidXmlException();
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $parsed
     * @return array<int, array<string, mixed>>
     */
    public static function list(array $parsed, string $key): array
    {
        $value = self::value($parsed, $key);

        if (is_null($value)) {
            $value = [];
        }

        return is_array($value) && array_is_list($value) ? $value : [$value];
    }

    /**
     * @param array<string, mixed> $parsed
     * @return array<string, string>
     * @throws InvalidXmlException
     */
    public static function assoc(array $parsed, string $key): array
    {
        $value = self::value($parsed, $key);

        if (is_null($value)) {
            throw new InvalidXmlException();
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private static function value(array $parsed, string $key): mixed
    {
        $value = $parsed;
        $parts = explode('.', $key);

        do {
            $part = array_shift($parts);
            $value = is_array($value) && isset($value[$part]) ? $value[$part] : null;
        } while (count($parts) > 0);

        return $value;
    }
}
