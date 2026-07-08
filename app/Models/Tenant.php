<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'owner_user_id',
        'logo_url',
        'phone',
        'email',
        'status',
        'membership_type',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'tenant_id');
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class, 'tenant_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'tenant_id');
    }
}
