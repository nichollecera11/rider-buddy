<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceLog extends Model
{
    /** @use HasFactory<\Database\Factories\MaintenanceLogFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_motorcycle_id',
        'mechanic_id',
        'service_type',
        'description',
        'odometer_reading',
        'service_date',
        'cost',
        'is_verified_by_mechanic'
    ];

    //Relationship
    public function motorcycle(){
        return $this->belongsTo(UserMotorcycle::class, 'user_motorcycle_id');
    }
    public function mechanic(){
        return $this->belongsTo(Mechanic::class);
    }

}
