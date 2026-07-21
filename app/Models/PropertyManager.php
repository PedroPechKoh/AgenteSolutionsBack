<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_user_id',
        'autonomo_user_id',
        'tenant_id',
        'status',
        'linked_at',
        'revoked_at',
        'grace_period_until',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function autonomo()
    {
        return $this->belongsTo(User::class, 'autonomo_user_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function assignedProperties()
    {
        return $this->belongsToMany(Property::class, 'property_manager_properties', 'property_manager_id', 'property_id');
    }
}
