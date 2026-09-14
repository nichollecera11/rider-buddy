<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiagnosticReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'consultation_id',
        'mechanic_id',
        'findings',
        'recommended_repairs',
        'severity',
        'status',
        'issued_at',
        'disputed_by',
        'dispute_reason',
    ];

    // The booking this report belongs to
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    // The mechanic who issued the report
    public function mechanic()
    {
        return $this->belongsTo(Mechanic::class);
    }

    // The user who disputed the report (if applicable)
    public function disputedBy()
    {
        return $this->belongsTo(User::class, 'disputed_by');
    }
    // The maintenance logs/repairs that were done as a result of this diagnosis
    public function maintenanceLogs()
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}