<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LTOComplianceMedia extends Model
{
    /** @use HasFactory<\Database\Factories\LTOComplianceMediaFactory> */
    use HasFactory;
    protected $fillable = [
        'l_t_o_compliance_id',
        'file_path',
        'document_type'
    ];

    public function lto_compliance()
    {
        return $this->belongsTo(LTOCompliance::class);
    }
    
}
