<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Sas;

final class AccountSasPermissions
{
    public function __construct(
        public readonly bool $read = false,
        public readonly bool $write = false,
        public readonly bool $delete = false,
        public readonly bool $permanentDelete = false,
        public readonly bool $list = false,
        public readonly bool $add = false,
        public readonly bool $create = false,
        public readonly bool $update = false,
        public readonly bool $process = false,
        public readonly bool $tags = false,
        public readonly bool $filter = false,
        public readonly bool $setImmutabilityPolicy = false,
    ) {}

    public function __toString(): string
    {
        $permissions = "";

        if ($this->read) {
            $permissions .= "r";
        }
        if ($this->write) {
            $permissions .= "w";
        }
        if ($this->delete) {
            $permissions .= "d";
        }
        if ($this->permanentDelete) {
            $permissions .= "y";
        }
        if ($this->list) {
            $permissions .= "l";
        }
        if ($this->add) {
            $permissions .= "a";
        }
        if ($this->create) {
            $permissions .= "c";
        }
        if ($this->update) {
            $permissions .= "u";
        }
        if ($this->process) {
            $permissions .= "p";
        }
        if ($this->tags) {
            $permissions .= "t";
        }
        if ($this->filter) {
            $permissions .= "f";
        }
        if ($this->setImmutabilityPolicy) {
            $permissions .= "i";
        }

        return $permissions;
    }
}
