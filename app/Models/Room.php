<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name'];

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }
}