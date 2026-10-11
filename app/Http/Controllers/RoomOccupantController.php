<?php

namespace App\Http\Controllers;

use App\Http\Resources\OccupantResource;
use App\Models\Room;
use App\Queries\ListRoomOccupants;

class RoomOccupantController extends Controller
{
    public function index(Room $room, ListRoomOccupants $query)
    {
        return OccupantResource::collection($query->execute($room));
    }
}