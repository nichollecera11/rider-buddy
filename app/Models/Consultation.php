<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consultation extends Model
{

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'mechanic_id',
        'user_motorcycle_id',
        'consultation_type',

        'issue_description',
        'agreed_diagnostic_fee',
        'estimated_repair_costs',
        'payment_status',
        'status',
        'suggested_parts',
        'latitude',
        'longitude',
        'location_name',
        'mechanic_notes',
        'verification_otp',
        'arrived_at'

    ];

    protected $casts = [
        'suggested_parts' => 'array',
        'agreed_diagnostic_fee' => 'decimal:2',
        'estimated_repair_costs' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'verification_otp_created_at' => 'datetime',

    ];
    protected $hidden = ['verification_otp'];

    // 🚀 MAO NI ANG NA-MISSING NIMO NGA RELATIONSHIP
    public function mechanic()
    {
        return $this->belongsTo(Mechanic::class);
    }

    // Para makuha ang motor details
    public function motorcycle()
    {
        return $this->belongsTo(UserMotorcycle::class, 'user_motorcycle_id');
    }

    // Para makuha ang details ni Nichol (ang Rider)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ang media relationship nga atong gi-fix ganiha
    public function media()
    {
        return $this->hasMany(ConsultationMedia::class, 'consultation_id');
    }
    public function diagnosticReport()
    {
        return $this->hasOne(DiagnosticReport::class);
    }
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function isReviewable(): bool
    {
        return $this->status === 'completed';
    }

    public function attachMedia(Request $request): void
    {
        foreach (['images' => 'image', 'videos' => 'video'] as $input => $type) {
            foreach ($request->file($input, []) as $file) {
                $this->media()->create([
                    'file_path' => $file->store('consultation/media', 'public'),
                    'file_type' => $type,
                ]);
            }
        }
    }


    public static function generateVerificationOtp(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    public function refreshVerificationOtp(): string
    {
        $otp = self::generateVerificationOtp();

        $this->forceFill([
            'verification_otp' => $otp,
            'verification_otp_created_at' => now(),
        ])->save();

        return $otp;
    }

    public function verifyOtp(string $input): array
    {
        if (
            !$this->verification_otp ||
            !hash_equals((string) $this->verification_otp, $input)
        ) {
            return [false, 'Invalid OTP code. Verification Failed'];
        }

        if (!$this->verification_otp_created_at) {
            return [false, 'OTP timestamp is missing. Please refresh the QR code.'];
        }

        // Temporary two-minute expiry for testing.
        if ($this->verification_otp_created_at->lt(now()->subMinutes(2))) {
            return [false, 'QR Code Expired, Rider Needs to Refresh the QR'];
        }

        return [true, null];
    }


    public function isCancellable(): bool
    {
        return $this->status === 'pending';
    }

    public function cancel(): void
    {
        $this->media()->delete(); // soft-deletes media rows if ConsultationMedia uses SoftDeletes too
        $this->delete(); // soft-deletes the consultation
    }
    // 9/24/2026
    public function isAssignedMechanic(User $user): bool
    {
        return $this->mechanic && $this->mechanic->user_id === $user->id;
    }


    public static function findUnresolvedSOS(int $userId): ?self
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('consultation_type', 'sos')
            ->whereIn('status', ['pending', 'accepted', 'ongoing'])
            ->latest()
            ->first();
    }


}
