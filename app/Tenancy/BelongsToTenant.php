<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Every query on this model gets: WHERE tenant_id = current tenant
        static::addGlobalScope('tenant', function (Builder $query) {
            $table = $query->getModel()->getTable();
            $query->where("{$table}.tenant_id", app(TenantContext::class)->id());
        });

        // Every new record gets the current tenant_id automatically
        static::creating(function ($model) {
            if (! $model->tenant_id) {
                $model->tenant_id = app(TenantContext::class)->id();
            }
        });
    }
}