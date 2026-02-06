<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable=[
        'path',
        'imageable_id',
        'imageable_type',
        'is_primary',
    ];
    public function imageable(){
        return $this->belongsTo(Image::class);
    }
}
