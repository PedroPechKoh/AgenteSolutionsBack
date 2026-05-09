<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $fillable = [
        'property_id',
        'property_area_id',
        'requested_by',
        'assigned_to',
        'service_category_id',
        'service_type',
        'priority',
        'status',
        'title',
        'description',
        'evidence_path',
        'scheduled_start',
        'scheduled_end',
        'real_start',
        'real_end',
        'supervisor_name',
        'custom_checklist'
    ];

    protected $casts = [
        'custom_checklist' => 'array',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function area()
    {
        return $this->belongsTo(PropertyArea::class, 'property_area_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function components()
    {
        return $this->belongsToMany(PropertyComponent::class, 'service_component');
    }

    public function technicians()
    {
        return $this->belongsToMany(User::class, 'service_technician', 'service_id', 'technician_id');
    }
}

