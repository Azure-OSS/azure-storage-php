<?php

declare(strict_types=1);

namespace AzureOss\Storage\Blob\Sas;

final class BlobSasPermissions
{
    public function __construct(
        public bool $read = false,
        public bool $add = false,
        public bool $create = false,
        public bool $write = false,
        public bool $delete = false,
        public bool $deleteVersion = false,
        public bool $permanentDelete = false,
        public bool $tags = false,
        public bool $list = false,
        public bool $move = false,
        public bool $execute = false,
        public bool $ownership = false,
        public bool $permissions = false,
        public bool $setImmutabilityPolicy = false,
    ) {}

    public function __toString(): string
    {
        $permissions = '';

        if ($this->read) {
            $permissions .= 'r';
        }
        if ($this->add) {
            $permissions .= 'a';
        }
        if ($this->create) {
            $permissions .= 'c';
        }
        if ($this->write) {
            $permissions .= 'w';
        }
        if ($this->delete) {
            $permissions .= 'd';
        }
        if ($this->deleteVersion) {
            $permissions .= 'x';
        }
        if ($this->permanentDelete) {
            $permissions .= 'y';
        }
        if ($this->list) {
            $permissions .= 'l';
        }
        if ($this->tags) {
            $permissions .= 't';
        }
        if ($this->move) {
            $permissions .= 'm';
        }
        if ($this->execute) {
            $permissions .= 'e';
        }
        if ($this->ownership) {
            $permissions .= 'o';
        }
        if ($this->permissions) {
            $permissions .= 'p';
        }
        if ($this->setImmutabilityPolicy) {
            $permissions .= 'i';
        }

        return $permissions;
    }
}
