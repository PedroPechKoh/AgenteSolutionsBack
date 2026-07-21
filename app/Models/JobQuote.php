<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_request_id',
        'technician_id',
        'price',
        'estimated_days',
        'message',
        'status',
    ];

    public function jobRequest()
    {
        return $this->belongsTo(JobRequest::class, 'job_request_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
