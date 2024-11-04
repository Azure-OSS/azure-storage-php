<?php

declare(strict_types=1);

namespace AzureOss\Storage\Common\Sas;

final class AccountSasServices
{
    public function __construct(
        public bool $blob = false,
        public bool $queue = false,
        public bool $table = false,
        public bool $file = false,
    ) {}

    public function __toString(): string
    {
        $permissions = "";

        if ($this->blob) {
            $permissions .= "b";
        }
        if ($this->queue) {
            $permissions .= "q";
        }
        if ($this->table) {
            $permissions .= "t";
        }
        if ($this->file) {
            $permissions .= "f";
        }

        return $permissions;
    }
}
