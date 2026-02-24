<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory; // 2. I-use ni sa sulod sa class

    protected $fillable = [
        'seller_id',
        'category_id',
        'brand_id',
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
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function brand(){
        return $this->belongsTo(Brand::class);
    }
    public function images(){
        return $this->morphMany(Image::class, 'imageable');
    }
}
