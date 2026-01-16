<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Helpers;

use AzureOss\Storage\Blob\Exceptions\DeserializationException;

/**
 * @internal
 */
final class DateHelper
{
    public static function formatAs8601Zulu(\DateTimeInterface $date): string
    {
        return \DateTime::createFromInterface($date)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z');
    }

    public static function deserializeDateRfc1123Date(string $date): \DateTimeInterface
    {
        $result = \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC1123, $date);
        if ($result === false) {
            throw new DeserializationException('Azure returned a malformed date.');
        }

        return $result;
    }
}
