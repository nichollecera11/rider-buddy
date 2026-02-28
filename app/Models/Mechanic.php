<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mechanic extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
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
        'rating',
        'latitude',
        'longitude',
        'service_fee_starts_at',
        'is_24_7',
        'offers_towing',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function scopeWithDistance($query, $lat, $lng)
    {
        // Kini nga formula mo-calculate sa distance (in kilometers)
        return $query->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", [$lat, $lng, $lat]);
    }
}
