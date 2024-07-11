<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Helpers;

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
}
