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
        'user_motorcycle_id',
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

    // 🚀 MAO NI ANG NA-MISSING NIMO NGA RELATIONSHIP
    public function mechanic()
    {
        return $this->belongsTo(Mechanic::class);
    }

    // Para makuha ang motor details
    public function motorcycle()
    {
        return $this->belongsTo(UserMotorcycle::class, 'user_motorcycle_id');
    }

    // Para makuha ang details ni Nichol (ang Rider)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ang media relationship nga atong gi-fix ganiha
    public function media()
    {
        return $this->hasMany(ConsultationMedia::class, 'consultation_id');
    }
}
