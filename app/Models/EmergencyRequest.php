<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyRequest extends Model
{
    use HasFactory;

    protected $table = 'emergency_requests';

    protected $fillable = [
        'requester_id',
        'blood_group',
        'units_required',
        'patient_name',
        'hospital_name',
        'district',
        'contact_number',
        'emergency_message',
        'priority',
        'status',
        'expires_at',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'units_required' => 'integer',
        'expires_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user who requested the blood.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the donor responses for this emergency request.
     */
    public function responses()
    {
        return $this->hasMany(EmergencyResponse::class, 'request_id');
    }
}
