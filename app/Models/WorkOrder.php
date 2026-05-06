<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    use HasFactory;

    protected $table = 'work_orders';

    protected $fillable = [
        'property_id',
        'type',
        'zone',
        'equipment',
        'description',
        'evidence_path',
        'evidence_path_2',
        'status',
        'priority',
        'tecnico_id',
        'custom_checklist'
    ];

    protected $casts = [
        'custom_checklist' => 'array'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }
}
