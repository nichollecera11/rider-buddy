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
}
