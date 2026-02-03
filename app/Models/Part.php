<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory; // 2. I-use ni sa sulod sa class

    protected $guarded = [];

   /* public function seller()
{
    // Usahay kinahanglan nimo i-specify ang table kung singular imong gamit
    //return $this->belongsTo(Seller::class, 'seller_id');
}*/
}
