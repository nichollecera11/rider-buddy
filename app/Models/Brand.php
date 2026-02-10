<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo',
    ];

    public function motorcycles()
    {
        return $this->hasMany(Motorcycle::class);
    }
}
