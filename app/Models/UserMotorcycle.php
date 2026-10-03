<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class UserMotorcycle extends Model
{
    /** @use HasFactory<\Database\Factories\UserMotorcycleFactory> */
    use HasFactory;

    use HasFactory, SoftDeletes; 
    

    protected $fillable = [
    'user_id',
    'brand_id',
    'model',
    'year_model',
    'engine_capacity',
    'transmission',
    'fuel_type',
    'color',
    'plate_number',
    'engine_number',
    'chassis_number',
    'last_registration_date',
    'insurance_expiry',
    'current_odometer',
    'is_main',
    'is_active',
    //verification for odometer and last registration
    // 'verification_photo'
];

    // Ang tag-iya sa motor
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ang brand (Honda, Yamaha, etc.)
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    // Ang iyang mga pictures (MorphMany)
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function media(){
        return $this->hasMany(UserMotorcycleMedia::class);
    }

    public function setAsMain(): void 
    {
        static::where('user_id', $this->user_id)
        ->where('id', '!=', $this->id)
        ->update(['is_main' => false]);

        $this->is_main = true;
    }

    
}
