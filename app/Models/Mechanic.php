<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mechanic extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'shop_name',
        'address',
        'bio',
        'specialization',
        'contact_number',
        'emergency_contact',
        'years_experience',
        'is_verified',
        'is_available',
        'diagnostic_fee_base',
        'latitude',
        'longitude',
        'service_fee_starts_at',
        'is_24_7',
        'offers_towing',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_available' => 'boolean',
        'is_24_7' => 'boolean',
        'offers_towing' => 'boolean',

        // Mao ni ang importante para sa kwarta:
        'diagnostic_fee_base' => 'decimal:2',
        'service_fee_starts_at' => 'decimal:2',

        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function profilePicture()
    {
        return $this->hasOne(MechanicMedia::class)->where('collection', 'profile');
    }

    // 1. DEDICATED MEDIA (Dili na Polymorphic)
    public function media()
    {
        // Gamita ang imong bag-ong MechanicMedia model
        return $this->hasMany(MechanicMedia::class);
    }

    // 2. REVIEWS (Polymorphic gihapon ni kay ang Reviews 
// pwede man sa Parts, Motorcycle, ug Mechanic)
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // 3. DISTANCE CALCULATION (Perfect na ni!)
    public function scopeWithDistance($query, $lat, $lng)
    {
        return $query->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [$lat, $lng, $lat]);
    }

    // 4. RATING ATTRIBUTE (With Safe Null Handling)
    public function getAverageRatingAttribute()
    {
        // I-check nato kung na-load na ba ang reviews para dili sige'g query
        if (!$this->relationLoaded('reviews')) {
            return (float) ($this->rating ?? 0); // Gamita ang cached rating sa table kung wala na-load
        }

        return round($this->reviews->avg('rating'), 1) ?: 0.0;
    }
}
