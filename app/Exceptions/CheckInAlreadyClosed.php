<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class CheckInAlreadyClosed extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This check-in is already checked out.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}