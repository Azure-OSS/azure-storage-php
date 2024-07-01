<?php

declare(strict_types=1);

namespace AzureOss\Storage\Requests;

enum BlockType
{
    case COMMITTED;
    case UNCOMMITTED;
    case LATEST;
}
