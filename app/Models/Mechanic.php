<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mechanic extends Model
{
    use HasFactory;
    protected $fillable = [
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
