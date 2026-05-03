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

    public function parent()
    {
        return $this->belongsTo(PropertyArea::class, 'parent_id');
    }

    public function subAreas()
    {
        return $this->hasMany(PropertyArea::class, 'parent_id');
    }
}