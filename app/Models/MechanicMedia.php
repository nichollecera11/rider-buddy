<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MechanicMedia extends Model
{
    /** @use HasFactory<\Database\Factories\MechanicMediaFactory> */
    use HasFactory;
    protected $fillable = [
        'mechanic_id',
        'file_path',
        'file_type',
        'collection'
    ];

    public function mechanic(){
        return $this->belongsTo(Mechanic::class);
    }
}
