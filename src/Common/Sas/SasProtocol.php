<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Sas;

enum SasProtocol: string
{
    case HTTPS = "https";
    case HTTPS_AND_HTTP = "https,http";
}
