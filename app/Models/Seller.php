<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image',
        'shop_name',
        'address',
        'contact_number',
        'business_permit_no',
        'has_delivery',
        'description',
        'latitude',
        'longitude',
        'is_official_store',
        'is_verified',
        'is_24_7'
    ];

    public function motorcycles()
    {
        return $this->hasMany(Motorcycle::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function parts()
    {
        return $this->hasMany(Part::class);
    }
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

}
