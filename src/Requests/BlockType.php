<?php

declare(strict_types=1);

namespace Brecht\FlysystemAzureBlobStorage\Requests;

enum BlockType
{
    case COMMITTED;
    case UNCOMMITTED;
    case LATEST;
}
