<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyComponent extends Model
{
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_component');
    }
}
