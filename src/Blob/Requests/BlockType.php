<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Requests;

/**
 * @internal
 */
enum BlockType: string
{
    case COMMITTED = "Committed";
    case UNCOMMITTED = "Uncommitted";
    case LATEST = "Latest";
}
