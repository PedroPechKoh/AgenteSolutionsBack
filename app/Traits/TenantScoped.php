<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;

trait TenantScoped
{
    /**
     * Boot the tenant scoped trait for a model.
     */
    protected static function bootTenantScoped(): void
    {
        // 1. Agregar el Global Scope de filtrado automático
        static::addGlobalScope(new TenantScope);

        // 2. Al crear un nuevo registro, si no tiene tenant_id y el usuario conectado
        // no es Root y pertenece a un tenant, asignárselo automáticamente.
        static::creating(function ($model) {
            if (auth()->check()) {
                $user = auth()->user();
                if ($user && $user->role_id !== 0 && !is_null($user->tenant_id) && empty($model->tenant_id)) {
                    $model->tenant_id = $user->tenant_id;
                }
            }
        });
    }

    /**
     * Relación con el Tenant dueño del registro.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
