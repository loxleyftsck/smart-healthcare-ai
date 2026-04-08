<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * Automatically filters queries to only include records belonging to the current tenant.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = auth()->user()?->tenant_id ?? null;

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }
}
