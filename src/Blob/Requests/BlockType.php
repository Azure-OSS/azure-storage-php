<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

/**
 * @internal
 */
enum BlockType
{
    case COMMITTED;
    case UNCOMMITTED;
    case LATEST;
}
