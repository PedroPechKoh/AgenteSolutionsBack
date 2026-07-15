<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    protected $fillable = ['name', 'icon', 'category'];

    public function technicians()
    {
        return $this->belongsToMany(User::class, 'technician_specialties', 'specialty_id', 'user_id');
    }
}
