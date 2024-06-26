<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

enum BlockType
{
    case COMMITTED;
    case UNCOMMITTED;
    case LATEST;
}
