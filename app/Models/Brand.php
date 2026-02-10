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
    public function parts() {
        return $this->hasMany(Part::class);
    }
}
