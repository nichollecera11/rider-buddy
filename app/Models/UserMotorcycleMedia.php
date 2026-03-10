<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMotorcycleMedia extends Model
{
    /** @use HasFactory<\Database\Factories\UserMotorcycleMediaFactory> */
    use HasFactory;

    protected $fillable = [
        'user_motorcycle_id',
        'file_path',
        'file_type',
        'collection'
    ];

    public function media(){
        return $this->belongsTo(UserMotorcycle::class, 'user_motorcycle_id');
    }
}
