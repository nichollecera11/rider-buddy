<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationMedia extends Model
{

    use SoftDeletes;

    protected $table = 'consultations_media';

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