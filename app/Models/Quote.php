<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'concept' => 'array',
        'chat_history' => 'array',
        'mp_payment_data' => 'array',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }
}