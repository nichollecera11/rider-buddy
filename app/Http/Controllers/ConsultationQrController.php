<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\ConsultationQrChallenge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ConsultationQrController extends Controller
{
    /**
     * Generate or refresh a QR challenge for the assigned rider.
     */
    public function store(Request $request, Consultation $consultation): JsonResponse
    {
        // Only the rider who created this consultation may generate its QR.
        if ($consultation->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to generate this QR code.',
            ], 403);
        }

        // The mechanic must accept the consultation first.
        if ($consultation->status !== 'accepted') {
            return response()->json([
                'message' => 'The consultation must be accepted before generating a QR code.',
            ], 422);
        }

        $result = DB::transaction(function () use ($consultation) {
            // Lock the consultation to prevent concurrent QR generation.
            $lockedConsultation = Consultation::whereKey($consultation->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Recheck status after acquiring the lock.
            if ($lockedConsultation->status !== 'accepted') {
                return null;
            }

            // Revoke all previous unconfirmed challenges.
            ConsultationQrChallenge::where('consultation_id', $lockedConsultation->id)
                ->whereNull('confirmed_at')
                ->whereNull('revoked_at')
                ->update([
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);

            // Generate a secure, unpredictable token.
            $token = bin2hex(random_bytes(32));

            $challenge = ConsultationQrChallenge::create([
                'consultation_id' => $lockedConsultation->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addSeconds(60),
            ]);

            return [
                'token' => $token,
                'expires_at' => $challenge->expires_at,
            ];
        });

        if ($result === null) {
            return response()->json([
                'message' => 'The consultation is no longer available for QR verification.',
            ], 422);
        }

        return response()->json([
            'message' => 'QR challenge generated successfully.',
            'data' => [
                'token' => $result['token'],
                'expires_at' => $result['expires_at']->toISOString(),
                'expires_in_seconds' => 60,
            ],
        ], 201);
    }


    /**
     * Mechanic scans the rider's QR challenge.
     * Scanning does not start the consultation.
     * The rider must confirm arrival separately.
     */
    public function scan(Request $request, Consultation $consultation): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
        ]);

        $user = $request->user();
        $mechanic = $user->mechanic;

        // Confirm this account belongs to the assigned mechanic.
        if (
            !$user->is_active
            || !$user->isMechanic()
            || !$mechanic
            || !$mechanic->is_verified
            || $consultation->mechanic_id !== $mechanic->id
        ) {
            return response()->json([
                'message' => 'You are not authorized to scan this consultation QR code.',
            ], 403);
        }

        $result = DB::transaction(function () use ($consultation, $validated, $user) {
            $lockedConsultation = Consultation::whereKey($consultation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedConsultation->status !== 'accepted') {
                return ['error' => 'This consultation is not awaiting arrival verification.', 'status' => 422];
            }

            $challenge = ConsultationQrChallenge::where(
                'consultation_id',
                $lockedConsultation->id
            )
                ->where('token_hash', hash('sha256', $validated['token']))
                ->lockForUpdate()
                ->first();

            if (!$challenge) {
                return ['error' => 'Invalid QR code.', 'status' => 422];
            }

            if ($challenge->revoked_at !== null) {
                return ['error' => 'This QR code has been revoked.', 'status' => 422];
            }

            if ($challenge->confirmed_at !== null) {
                return ['error' => 'This QR code has already been used.', 'status' => 422];
            }

            if ($challenge->expires_at->isPast()) {
                return ['error' => 'This QR code has expired.', 'status' => 422];
            }

            if ($challenge->scanned_at !== null) {
                return ['error' => 'This QR code has already been scanned.', 'status' => 422];
            }

            $challenge->update([
                'scanned_by_user_id' => $user->id,
                'scanned_at' => now(),
                'confirmation_expires_at' => now()->addMinutes(2),
            ]);

            return [
                'challenge_id' => $challenge->id,
                'consultation_id' => $lockedConsultation->id,
                'confirmation_expires_at' => $challenge->confirmation_expires_at,
            ];
        });

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'message' => 'QR scanned successfully. Waiting for rider confirmation.',
            'data' => $result,
        ]);
    }
    /**
     * Rider checks whether the assigned mechanic has scanned the QR.
     */
    public function pendingScan(
        Request $request,
        Consultation $consultation
    ): JsonResponse {
        // Only the rider who owns this consultation can check.
        if ($consultation->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to view this verification.',
            ], 403);
        }

        if ($consultation->status !== 'accepted') {
            return response()->json([
                'message' => 'This consultation is not awaiting arrival verification.',
            ], 422);
        }

        $challenge = $consultation->qrChallenges()
            ->whereNotNull('scanned_at')
            ->whereNull('confirmed_at')
            ->whereNull('revoked_at')
            ->where('confirmation_expires_at', '>', now())
            ->latest()
            ->first();

        if (!$challenge) {
            return response()->json([
                'message' => 'No pending mechanic scan found.',
                'data' => null,
            ]);
        }

        $mechanic = $consultation->mechanic;

        return response()->json([
            'message' => 'Mechanic scan detected. Rider confirmation required.',
            'data' => [
                'challenge_id' => $challenge->id,
                'consultation_id' => $consultation->id,
                'confirmation_expires_at' => $challenge->confirmation_expires_at->toISOString(),
                'mechanic' => [
                    'name' => $mechanic?->name,
                    'shop_name' => $mechanic?->shop_name,
                ],
            ],
        ]);
    }


    /**
     * Rider confirms or rejects the mechanic's arrival.
     */
    public function confirmArrival(
        Request $request,
        Consultation $consultation
    ): JsonResponse {
        $validated = $request->validate([
            'challenge_id' => ['required', 'integer'],
            'confirmed' => ['required', 'boolean'],
        ]);

        // Only the rider who owns this consultation can confirm arrival.
        if ($consultation->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to confirm this arrival.',
            ], 403);
        }

        $result = DB::transaction(function () use ($consultation, $validated) {
            // Lock the consultation to prevent simultaneous state changes.
            $lockedConsultation = Consultation::whereKey($consultation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedConsultation->status !== 'accepted') {
                return [
                    'error' => 'This consultation is no longer awaiting arrival confirmation.',
                    'status' => 422,
                ];
            }

            // Only accept a challenge belonging to this consultation.
            $challenge = $lockedConsultation->qrChallenges()
                ->whereKey($validated['challenge_id'])
                ->lockForUpdate()
                ->first();

            if (!$challenge) {
                return [
                    'error' => 'Invalid QR verification request.',
                    'status' => 404,
                ];
            }

            if (
                $challenge->revoked_at !== null ||
                $challenge->confirmed_at !== null
            ) {
                return [
                    'error' => 'This QR verification request has already been used or revoked.',
                    'status' => 422,
                ];
            }

            if (!$challenge->scanned_at || !$challenge->scanned_by_user_id) {
                return [
                    'error' => 'The mechanic has not scanned this QR code.',
                    'status' => 422,
                ];
            }

            // Ensure the scan came from the mechanic assigned to this consultation.
            $assignedMechanicUserId = $lockedConsultation->mechanic()
                ->value('user_id');

            if (
                !$assignedMechanicUserId ||
                (int) $challenge->scanned_by_user_id !== (int) $assignedMechanicUserId
            ) {
                return [
                    'error' => 'The QR scan does not match the assigned mechanic.',
                    'status' => 403,
                ];
            }

            // The rider must respond within the two-minute confirmation window.
            if (
                !$challenge->confirmation_expires_at ||
                $challenge->confirmation_expires_at->isPast()
            ) {
                $challenge->update([
                    'revoked_at' => now(),
                ]);

                return [
                    'error' => 'The confirmation request has expired. Please generate a new QR code.',
                    'status' => 422,
                ];
            }

            if ($validated['confirmed']) {
                // Only rider confirmation starts the consultation.
                $challenge->update([
                    'confirmed_at' => now(),
                ]);

                $lockedConsultation->forceFill([
                    'status' => 'ongoing',
                    'arrived_at' => now(),
                ])->save();

                return [
                    'confirmed' => true,
                    'status' => 'ongoing',
                    'arrived_at' => $lockedConsultation->arrived_at,
                ];
            }

            // Rider rejected the arrival. Keep consultation accepted.
            $challenge->update([
                'revoked_at' => now(),
            ]);

            return [
                'confirmed' => false,
                'status' => 'accepted',
            ];
        });

        if (isset($result['error'])) {
            return response()->json([
                'message' => $result['error'],
            ], $result['status']);
        }

        return response()->json([
            'message' => $result['confirmed']
                ? 'Mechanic arrival confirmed. Consultation started.'
                : 'Mechanic arrival rejected. Consultation remains accepted.',
            'data' => $result,
        ]);
    }



}
