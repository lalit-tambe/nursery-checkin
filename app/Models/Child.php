<?php

namespace App\Models;

use App\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Child extends Model
{
    use BelongsToTenant;

    protected $table = 'children';

    protected $fillable = ['tenant_id', 'name'];
}