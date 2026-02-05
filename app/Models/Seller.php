<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasFactory;

    protected $fillable =[
        'user_id',
        'image',
        'shop_name',
        'address',
        'contact_number',
        'business_permit_no',
        'has_delivery',
    ];

    }
