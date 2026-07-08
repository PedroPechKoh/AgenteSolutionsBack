<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class Technician extends Model
{
    use TenantScoped;
    protected $fillable = ['tenant_id'];
}
