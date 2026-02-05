<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mechanic extends Model
{
    protected $fillable =[
        'user_id',
        'name',
        'shop_name',
        'address',
        'contact_number',
        'years_experience',
        'is_verified',
        'service_fee_starts_at',
        'image',
    ];
}
