<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LTOCompliance extends Model
{
    /** @use HasFactory<\Database\Factories\LTOComplianceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_motorcycle_id',
        'plate_number',
        'engine_number',
        'chassis_number',
        'registration_expiry',
        'status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'remarks'
    ];

    protected $casts = [
        'registration_expiry' => 'date',
        'verified_at' => 'date'
    ];

    public function verifier() {
        return $this->belongsTo(User::class, 'verified_by');
    }
    public function motorcycle() {
        return $this->belongsTo(UserMotorcycle::class, 'user_motorcycle_id');
    }
    // public function media(): HasMany {
    //     return $this->hasMany(UserMotorcycleMedia::class, 'user_motorcycle_id', 'user_motorcycle_id');
    // }
}
