<?php

namespace App\Actions;

use App\Exceptions\ChildAlreadyCheckedIn;
use App\Models\CheckIn;
use App\Models\Room;
use Illuminate\Database\UniqueConstraintViolationException;

class CheckInChild
{
    public function execute(Room $room, int $childId): CheckIn
    {
        $open = $this->openCheckInFor($childId);

        if ($open) {
            throw new ChildAlreadyCheckedIn($open->room->name);
        }

        try {
            return CheckIn::create([
                'room_id' => $room->id,
                'child_id' => $childId,
                'checked_in_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Two requests at the same moment: the database index stopped the second one.
            throw new ChildAlreadyCheckedIn($this->openCheckInFor($childId)?->room->name);
        }
    }

    private function openCheckInFor(int $childId): ?CheckIn
    {
        return CheckIn::where('child_id', $childId)
            ->whereNull('checked_out_at')
            ->first();
    }
}