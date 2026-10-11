<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\NurseryHelpers;
use Tests\TestCase;

class CheckInBehaviourTest extends TestCase
{
    use NurseryHelpers;
    use RefreshDatabase;

    private const TENANT = 1;

    public function test_child_can_be_checked_in_and_appears_in_occupants(): void
    {
        $room = $this->makeRoom(self::TENANT);
        $child = $this->makeChild(self::TENANT, 'Ava');

        $this->asTenant(self::TENANT)
            ->postJson("/rooms/{$room->id}/check-ins", ['child_id' => $child->id])
            ->assertCreated();

        $this->asTenant(self::TENANT)
            ->getJson("/rooms/{$room->id}/occupants")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.child_name', 'Ava');
    }

    public function test_child_can_be_checked_out_and_leaves_occupants(): void
    {
        $this->freezeTime();
        $room = $this->makeRoom(self::TENANT);
        $child = $this->makeChild(self::TENANT);
        $checkIn = $this->makeOpenCheckIn($room, $child);

        $this->asTenant(self::TENANT)
            ->postJson("/check-ins/{$checkIn->id}/check-out")
            ->assertOk();

        $saved = CheckIn::withoutGlobalScopes()->find($checkIn->id);
        $this->assertSame(now()->toIso8601String(), $saved->checked_out_at->toIso8601String());

        $this->asTenant(self::TENANT)
            ->getJson("/rooms/{$room->id}/occupants")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_empty_room_returns_an_empty_list(): void
    {
        $room = $this->makeRoom(self::TENANT);

        $this->asTenant(self::TENANT)
            ->getJson("/rooms/{$room->id}/occupants")
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }

    public function test_second_check_in_into_a_different_room_is_rejected(): void
    {
        $sunflowers = $this->makeRoom(self::TENANT, 'Sunflowers');
        $explorers = $this->makeRoom(self::TENANT, 'Little Explorers');
        $child = $this->makeChild(self::TENANT);
        $this->makeOpenCheckIn($sunflowers, $child);

        $this->asTenant(self::TENANT)
            ->postJson("/rooms/{$explorers->id}/check-ins", ['child_id' => $child->id])
            ->assertStatus(409)
            ->assertJsonFragment(['message' => "Child is already checked in in room 'Sunflowers'. Check them out first."]);

        $this->assertDatabaseCount('check_ins', 1);
    }

    public function test_second_check_in_into_the_same_room_is_rejected(): void
    {
        $room = $this->makeRoom(self::TENANT);
        $child = $this->makeChild(self::TENANT);
        $this->makeOpenCheckIn($room, $child);

        $this->asTenant(self::TENANT)
            ->postJson("/rooms/{$room->id}/check-ins", ['child_id' => $child->id])
            ->assertStatus(409);

        $this->assertDatabaseCount('check_ins', 1);
    }

    public function test_checking_out_twice_is_rejected(): void
    {
        $room = $this->makeRoom(self::TENANT);
        $child = $this->makeChild(self::TENANT);
        $checkIn = $this->makeOpenCheckIn($room, $child);

        $this->asTenant(self::TENANT)
            ->postJson("/check-ins/{$checkIn->id}/check-out")
            ->assertOk();

        $this->asTenant(self::TENANT)
            ->postJson("/check-ins/{$checkIn->id}/check-out")
            ->assertStatus(409);
    }

    public function test_child_can_check_in_again_after_checking_out(): void
    {
        $sunflowers = $this->makeRoom(self::TENANT, 'Sunflowers');
        $explorers = $this->makeRoom(self::TENANT, 'Little Explorers');
        $child = $this->makeChild(self::TENANT);
        $checkIn = $this->makeOpenCheckIn($sunflowers, $child);

        $this->asTenant(self::TENANT)->postJson("/check-ins/{$checkIn->id}/check-out")->assertOk();

        $this->asTenant(self::TENANT)
            ->postJson("/rooms/{$explorers->id}/check-ins", ['child_id' => $child->id])
            ->assertCreated();
    }

    public function test_child_id_is_required(): void
    {
        $room = $this->makeRoom(self::TENANT);

        $this->asTenant(self::TENANT)
            ->postJson("/rooms/{$room->id}/check-ins", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('child_id');
    }

    public function test_unknown_child_id_is_rejected(): void
    {
        $room = $this->makeRoom(self::TENANT);

        $this->asTenant(self::TENANT)
            ->postJson("/rooms/{$room->id}/check-ins", ['child_id' => 9999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('child_id');
    }

    public function test_database_itself_refuses_two_open_check_ins_for_one_child(): void
    {
        // This proves the safety net works even if the code check is skipped
        // (for example, two staff tapping at the same moment).
        $room = $this->makeRoom(self::TENANT);
        $child = $this->makeChild(self::TENANT);
        $this->makeOpenCheckIn($room, $child);

        $this->expectException(UniqueConstraintViolationException::class);

        $this->makeOpenCheckIn($room, $child);
    }
}