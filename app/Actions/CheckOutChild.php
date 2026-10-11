<?php

namespace App\Actions;

use App\Exceptions\CheckInAlreadyClosed;
use App\Models\CheckIn;

class CheckOutChild
{
    public function execute(CheckIn $checkIn): CheckIn
    {
        // Only closes it if it is still open. Safe even if two requests arrive together.
        $closed = CheckIn::whereKey($checkIn->id)
            ->whereNull('checked_out_at')
            ->update(['checked_out_at' => now()]);

        if ($closed === 0) {
            throw new CheckInAlreadyClosed();
        }

        return $checkIn->refresh();
    }
}