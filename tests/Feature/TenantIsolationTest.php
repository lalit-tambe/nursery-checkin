<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\NurseryHelpers;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use NurseryHelpers;
    use RefreshDatabase;

    private const TENANT_A = 1;
    private const TENANT_B = 2;

    public function test_missing_tenant_header_is_rejected(): void
    {
        $room = $this->makeRoom(self::TENANT_A);

        $this->getJson("/rooms/{$room->id}/occupants")->assertStatus(400);
    }

    public function test_tenant_a_cannot_see_occupants_of_tenant_b_room(): void
    {
        $roomB = $this->makeRoom(self::TENANT_B);
        $childB = $this->makeChild(self::TENANT_B);
        $this->makeOpenCheckIn($roomB, $childB);

        $this->asTenant(self::TENANT_A)
            ->getJson("/rooms/{$roomB->id}/occupants")
            ->assertNotFound();
    }

    public function test_occupants_list_never_contains_children_of_another_tenant(): void
    {
        $roomA = $this->makeRoom(self::TENANT_A);
        $childA = $this->makeChild(self::TENANT_A, 'Ava');
        $this->makeOpenCheckIn($roomA, $childA);

        $roomB = $this->makeRoom(self::TENANT_B);
        $childB = $this->makeChild(self::TENANT_B, 'Ben');
        $this->makeOpenCheckIn($roomB, $childB);

        $this->asTenant(self::TENANT_A)
            ->getJson("/rooms/{$roomA->id}/occupants")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.child_name', 'Ava');
    }

    public function test_tenant_a_cannot_check_out_tenant_b_check_in(): void
    {
        $roomB = $this->makeRoom(self::TENANT_B);
        $childB = $this->makeChild(self::TENANT_B);
        $checkInB = $this->makeOpenCheckIn($roomB, $childB);

        $this->asTenant(self::TENANT_A)
            ->postJson("/check-ins/{$checkInB->id}/check-out")
            ->assertNotFound();

        // B's record must be unchanged: the child is still checked in.
        $this->assertDatabaseHas('check_ins', [
            'id' => $checkInB->id,
            'checked_out_at' => null,
        ]);
    }

    public function test_tenant_a_cannot_check_a_child_into_tenant_b_room(): void
    {
        $roomB = $this->makeRoom(self::TENANT_B);
        $childA = $this->makeChild(self::TENANT_A);

        $this->asTenant(self::TENANT_A)
            ->postJson("/rooms/{$roomB->id}/check-ins", ['child_id' => $childA->id])
            ->assertNotFound();

        $this->assertDatabaseCount('check_ins', 0);
    }

    public function test_tenant_a_cannot_check_tenant_b_child_into_its_own_room(): void
    {
        $roomA = $this->makeRoom(self::TENANT_A);
        $childB = $this->makeChild(self::TENANT_B);

        $this->asTenant(self::TENANT_A)
            ->postJson("/rooms/{$roomA->id}/check-ins", ['child_id' => $childB->id])
            ->assertStatus(422);

        $this->assertDatabaseCount('check_ins', 0);
    }
}