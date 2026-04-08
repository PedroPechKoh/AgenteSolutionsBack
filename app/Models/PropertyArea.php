<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyArea extends Model
{
    protected $guarded = [];

    public function components()
    {
        return $this->hasMany(PropertyComponent::class);
    }
}