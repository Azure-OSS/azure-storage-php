<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\SAS;

enum SASResource: string
{
    case BLOB = "b";
    case BLOB_VERSION = "bv";
    case BLOB_SNAPSHOT = "bs";
    case CONTAINER = "c";

    /**
     * @return SASPermission[]
     */
    public function getOrderOfPermissions(): array
    {
        return match ($this) {
            SASResource::BLOB,
            SASResource::BLOB_VERSION,
            SASResource::BLOB_SNAPSHOT => [SASPermission::READ, SASPermission::ADD, SASPermission::CREATE, SASPermission::WRITE, SASPermission::DELETE],
            SASResource::CONTAINER => [SASPermission::READ, SASPermission::ADD, SASPermission::CREATE, SASPermission::WRITE, SASPermission::DELETE, SASPermission::LIST],
        };
    }

    public function getResourceType(): SASResourceType
    {
        return match ($this) {
            self::BLOB,
            self::BLOB_VERSION,
            self::BLOB_SNAPSHOT => SASResourceType::BLOB,
            self::CONTAINER => SASResourceType::CONTAINER,
        };
    }
}
