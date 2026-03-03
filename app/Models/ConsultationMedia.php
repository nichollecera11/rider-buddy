<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationMedia extends Model
{
    protected $fillable = [
        'consultation_id',
        'file_path',
        'file_type' // 'image' o 'video'
    ];

    // Relationship balik sa Consultation
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}