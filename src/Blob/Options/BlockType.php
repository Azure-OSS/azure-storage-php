<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Options;

enum BlockType
{
    case COMMITTED;
    case UNCOMMITTED;
    case LATEST;
}
