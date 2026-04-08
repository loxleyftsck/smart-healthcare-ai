<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    /**
     * Boot the trait.
     * Register global scope and automatically set tenant_id on creation.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model) {
            if (!$model->tenant_id && auth()->user()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
