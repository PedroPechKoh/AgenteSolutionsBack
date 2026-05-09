<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkReport extends Model
{
    protected $fillable = [
        'service_id',
        'technician_id',
        'image_url',
        'description',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
