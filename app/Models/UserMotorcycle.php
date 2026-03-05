<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMotorcycle extends Model
{
    /** @use HasFactory<\Database\Factories\UserMotorcycleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'brand_id',
        'model',
        'year_model',
        'plate_number',
        'engine_number',
        'chassis_number',
        'color',
        'is_main'
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
}
