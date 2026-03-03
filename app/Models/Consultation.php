<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    /** @use HasFactory<\Database\Factories\ConsultationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mechanic_id',
        'motorcycle_id',
        'consultation_type',
        'issue_description',
        'agreed_diagnostic_fee',
        'estimated_repair_costs',
        'payment_status',
        'status',
        'suggested_parts',
        'latitude',
        'longitude',
        'location_name',
        'mechanic_notes',
        'verification_otp',
        'arrived_at'
    ];

    protected $casts = [
        'suggested_parts' => 'array',
        'agreed_diagnostic_fee' => 'decimal:2',
        'estimated_repair_costs' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function media()
    {
        return $this->hasMany(ConsultationMedia::class);
    }
}
