<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motorcycle extends Model
{
    use HasFactory; // 2. I-use ni sa sulod sa class

    protected $fillable =[
        'seller_id',
        'brand_id',
        'model',
        'year_model',
        'plate_number',
        'mileage',
        'price',
        'condition',
        'document_status',
        'is_registered',
        'description',
        'issues',
        'is_sold',
    ];

    public function seller() {
    return $this->belongsTo(Seller::class); 
    // if ang error sa api kay - Call to undefined relationship [seller] on model [App\Models\Motorcycle].
    }
    public function brand(){
        return $this->belongsTo(Brand::class);
    }
}
