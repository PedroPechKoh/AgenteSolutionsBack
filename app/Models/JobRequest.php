<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'contratista_user_id',
        'tenant_id',
        'client_id',
        'property_id',
        'title',
        'description',
        'specialty_id',
        'status',
        'selected_quote_id',
    ];

    public function contratista()
    {
        return $this->belongsTo(User::class, 'contratista_user_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function specialty()
    {
        return $this->belongsTo(Specialty::class, 'specialty_id');
    }

    public function quotes()
    {
        return $this->hasMany(JobQuote::class, 'job_request_id');
    }

    public function selectedQuote()
    {
        return $this->belongsTo(JobQuote::class, 'selected_quote_id');
    }
}
