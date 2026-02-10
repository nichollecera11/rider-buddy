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
        'part_name',
        'condition',
        'description',
        'price',
        'stock_quantity',
        'compatibility',
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
}
