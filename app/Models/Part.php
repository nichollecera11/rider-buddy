<?php

namespace App\Models;

use Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Part extends Model
{
    use HasFactory; // 2. I-use ni sa sulod sa class
    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'category_id',
        'brand_id',
        'slug',
        'part_name',
        'part_number',
        'type',
        'condition',
        'price',
        'is_negotiable',
        'stock_quantity',
        'oem_compatibility',
        'is_universal',
        'dimensions',
        'is_open_for_swap',
        'swap_preferences',
        'description',
        'location',
        'status',
        'main_image'
    ];

    protected $casts = [
        // 1. Kwarta/Numbers
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',

        // 2. Booleans (True/False)
        'is_negotiable' => 'boolean',
        'is_universal' => 'boolean',
        'is_open_for_swap' => 'boolean',

        // 3. JSON/Array (Optional Tip)
        // Kon ang 'oem_compatibility' listahan sa mga motor, 
        // puyde sab ni nimo himoon og array puhon.
    ];

    protected static function boot () {
        parent::boot();
        static::creating(function ($part){
         if (empty($part->slug)) {
            $part->slug = Str::slug($part->part_name) . '-' . rand(1000, 9999);
         }
         if (empty($part->status)){
            $part->status = 'available';
         }
        });
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
