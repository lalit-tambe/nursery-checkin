<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ChildAlreadyCheckedIn extends RuntimeException
{
    public function __construct(private ?string $roomName = null)
    {
        parent::__construct('Child is already checked in.');
    }

    public function render(): JsonResponse
    {
        $where = $this->roomName ? " in room '{$this->roomName}'" : '';

        return response()->json([
            'message' => "Child is already checked in{$where}. Check them out first.",
        ], 409);
    }
}