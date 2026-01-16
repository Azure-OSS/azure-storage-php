<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Models;

enum CopyStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case ABORTED = 'aborted';
    case FAILED = 'failed';
}
