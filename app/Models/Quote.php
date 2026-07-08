<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class Quote extends Model
{
    use TenantScoped;

    protected $guarded = [];

    protected $casts = [
        'concept' => 'array',
        'chat_history' => 'array',
        'mp_payment_data' => 'array',
        'advance_mp_data' => 'array',
        'remaining_mp_data' => 'array',
        'related_service_ids' => 'array',
        'is_unified_batch' => 'boolean',
        'advance_paid' => 'boolean',
        'remaining_paid' => 'boolean',
        'cash_requested' => 'boolean',
        'cash_confirmed' => 'boolean',
        'advance_paid_at' => 'datetime',
        'remaining_paid_at' => 'datetime',
        'cash_confirmed_at' => 'datetime',
    ];

    protected $appends = ['cash_confirmed_by_name'];

    public function getCashConfirmedByNameAttribute()
    {
        if ($this->cash_confirmed_by && $this->cashConfirmedBy) {
            return trim($this->cashConfirmedBy->first_name . ' ' . $this->cashConfirmedBy->last_name) ?: $this->cashConfirmedBy->name;
        }
        return null;
    }

    public function cashConfirmedBy()
    {
        return $this->belongsTo(User::class, 'cash_confirmed_by');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }
}