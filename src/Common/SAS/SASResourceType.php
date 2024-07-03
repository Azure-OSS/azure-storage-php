<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\SAS;

enum SASResourceType: string
{
    case BLOB = "blob";
    case CONTAINER = "container";
}
