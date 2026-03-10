<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerMedia extends Model
{
    /** @use HasFactory<\Database\Factories\SellerMediaFactory> */
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'file_path',
        'file_type',
        'collection'
    ];
    public function seller(){
        return $this->belongsTo(Seller::class);
    }
}
