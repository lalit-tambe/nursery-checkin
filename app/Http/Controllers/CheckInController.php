<?php

namespace App\Http\Controllers;

use App\Actions\CheckInChild;
use App\Actions\CheckOutChild;
use App\Http\Requests\CheckInRequest;
use App\Models\CheckIn;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class CheckInController extends Controller
{
    public function store(CheckInRequest $request, Room $room, CheckInChild $action): JsonResponse
    {
        $checkIn = $action->execute($room, $request->integer('child_id'));

        return response()->json($checkIn, 201);
    }

    public function checkOut(CheckIn $checkIn, CheckOutChild $action): JsonResponse
    {
        return response()->json($action->execute($checkIn));
    }
}