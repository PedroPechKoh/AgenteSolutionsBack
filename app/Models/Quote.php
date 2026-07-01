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
        'advance_mp_data' => 'array',
        'remaining_mp_data' => 'array',
        'advance_paid' => 'boolean',
        'remaining_paid' => 'boolean',
        'cash_requested' => 'boolean',
        'cash_confirmed' => 'boolean',
        'advance_paid_at' => 'datetime',
        'remaining_paid_at' => 'datetime',
        'cash_confirmed_at' => 'datetime',
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