<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\SAS;

/**
 * @see https://learn.microsoft.com/en-us/rest/api/storageservices/create-service-sas#permissions-for-a-directory-container-or-blob
 */
enum SASPermission: string
{
    case READ = "r";
    case ADD = "a";
    case CREATE = "c";
    case WRITE = "w";
    case DELETE = "d";
    case DELETE_VERSION = "x";
    case PERMANENT_DELETE = "y";
    case LIST = "l";
    case TAGS = "t";
    case FIND = "f";
    case MOVE = "m";
    case EXECUTE = "e";
    case OWNERSHIP = "o";
    case PERMISSIONS = "p";
    case SET_IMMUTABILITY_POLICY = "i";
}
