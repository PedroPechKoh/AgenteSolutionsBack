<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalWorkReport extends Model
{
    protected $fillable = [
        'service_id',
        'work_order_id',
        'folio',
        'report_date',
        'start_time',
        'end_time',
        'description',
        'materials',
        'observations',
        'selected_images',
    ];

    protected $casts = [
        'materials' => 'array',
        'selected_images' => 'array',
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
