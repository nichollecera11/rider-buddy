<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Motorcycle extends Model
{
    use HasFactory; // 2. I-use ni sa sulod sa class

    protected $fillable = [
        'seller_id',
        'brand_id',
        'model',
        'year_model',
        'color',
        'plate_number',
        'mileage',
        'engine_capacity',
        'transmission',
        'fuel_type',
        'current_location',
        'price',
        'is_negotiable',
        'condition',
        'document_status',
        'is_registered',
        'is_open_for_swap',
        'swap_preferences',
        'description',
        'issues',
        'is_sold',
    ];

    protected $casts = [
        // 1. Kwarta/Numbers
        'price' => 'decimal:2',
        'mileage' => 'integer',
        'engine_capacity' => 'integer',

        // 2. Booleans (True/False)
        'is_negotiable' => 'boolean',
        'is_registered' => 'boolean',
        'is_open_for_swap' => 'boolean',
        'is_sold' => 'boolean',

        // 3. Year (Para sa Year model)
        'year_model' => 'integer',
    ];


    public function seller()
    {
        return $this->belongsTo(Seller::class);
        // if ang error sa api kay - Call to undefined relationship [seller] on model [App\Models\Motorcycle].
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function images(): MorphMany
    {
        // Gigamit nato ang morphMany kay "Polymorphic" ang atong images table
        // (Puyde gamiton sa Seller, Mechanic, ug karon sa Motorcycle)
        return $this->morphMany(\App\Models\Image::class, 'imageable');
    }
}
