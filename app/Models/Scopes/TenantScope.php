<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Solo aplicar si hay un usuario autenticado en la petición (API o Web)
        if (auth()->check()) {
            $user = auth()->user();
            
            // Rol 0 es Root (Pedro): No se aplica filtro, tiene visión global de todo el sistema.
            // Si el usuario es de cualquier otro rol (1 Admin, 2 Técnico, 3 Cliente, 4 Autónomo)
            // y tiene un tenant_id asignado, filtramos estrictamente por ese tenant_id.
            if ($user && $user->role_id !== 0 && !is_null($user->tenant_id)) {
                $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
            }
        }
    }
}
