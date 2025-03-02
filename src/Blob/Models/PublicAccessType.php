<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

enum PublicAccessType: string
{
    case NONE = 'none';
    case BLOB = 'blob';
    case CONTAINER = 'container';
}
