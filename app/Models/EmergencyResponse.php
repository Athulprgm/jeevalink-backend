<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyResponse extends Model
{
    use HasFactory;

    protected $table = 'emergency_responses';

    protected $fillable = [
        'request_id',
        'donor_id',
        'response_status',
    ];

    /**
     * Get the emergency request.
     */
    public function request()
    {
        return $this->belongsTo(EmergencyRequest::class, 'request_id');
    }

    /**
     * Get the donor who responded.
     */
    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_id');
    }
}
