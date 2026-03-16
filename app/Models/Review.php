<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'reviewable_id',
        'reviewable_type',
        'consultation_id',
        'rating',
        'headline',
        'comment',
        'reply_comment'
    ];

    protected $casts = [
        'rating' => 'decimal:1'
    ];

    public function reviewable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function images()
    {
        // Kini ang mag-connect sa reviews table ngadto sa images table
        return $this->morphMany(Image::class, 'imageable');
    }
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
