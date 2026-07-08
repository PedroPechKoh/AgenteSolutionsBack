<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class Client extends Model
{
    use HasFactory, TenantScoped;

    protected $fillable = [
    'tenant_id',
    'name',
    'email',
    'phone',
    'address',
    'profile_picture' 
];
}