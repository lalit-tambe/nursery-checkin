<?php

namespace App\Tenancy;

use RuntimeException;

class TenantContext
{
    private ?int $id = null;

    public function set(int $id): void
    {
        $this->id = $id;
    }

    public function id(): int
    {
        if ($this->id === null) {
            throw new RuntimeException('Tenant is not set.');
        }

        return $this->id;
    }
}