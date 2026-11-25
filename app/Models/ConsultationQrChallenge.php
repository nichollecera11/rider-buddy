<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationQrChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'token_hash',
        'expires_at',
        'scanned_by_user_id',
        'scanned_at',
        'confirmation_expires_at',
        'confirmed_at',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'scanned_at' => 'datetime',
        'confirmation_expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }
}