<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkQuote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'chat_history' => 'array',
        'price' => 'decimal:2',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}