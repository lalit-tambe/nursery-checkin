<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OccupantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'check_in_id' => $this->id,
            'child_id' => $this->child_id,
            'child_name' => $this->child->name,
            'checked_in_at' => $this->checked_in_at->toIso8601String(),
        ];
    }
}