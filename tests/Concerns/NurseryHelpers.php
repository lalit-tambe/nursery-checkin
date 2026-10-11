<?php

namespace Tests\Concerns;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;

trait NurseryHelpers
{
    protected function makeRoom(int $tenantId, string $name = 'Sunflowers'): Room
    {
        return Room::create(['tenant_id' => $tenantId, 'name' => $name]);
    }

    protected function makeChild(int $tenantId, string $name = 'Ava'): Child
    {
        return Child::create(['tenant_id' => $tenantId, 'name' => $name]);
    }

    // A child who is in the room right now.
    protected function makeOpenCheckIn(Room $room, Child $child): CheckIn
    {
        return CheckIn::create([
            'tenant_id' => $room->tenant_id,
            'room_id' => $room->id,
            'child_id' => $child->id,
            'checked_in_at' => now(),
        ]);
    }

    // Sends the next request as this tenant.
    protected function asTenant(int $tenantId): static
    {
        return $this->withHeader('X-Tenant-ID', (string) $tenantId);
    }
}