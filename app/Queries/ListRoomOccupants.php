<?php

namespace App\Queries;

use App\Models\CheckIn;
use App\Models\Room;
use Illuminate\Database\Eloquent\Collection;

class ListRoomOccupants
{
    public function execute(Room $room): Collection
    {
        return CheckIn::with('child')
            ->where('room_id', $room->id)
            ->whereNull('checked_out_at')
            ->orderBy('checked_in_at')
            ->get();
    }
}