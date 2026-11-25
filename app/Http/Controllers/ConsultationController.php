<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Consultation;
use App\Models\Mechanic;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Requests\UpdateConsultationLocationRequest;



class ConsultationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        try {
            // 1. Base Query with Eager Loading (Walay Transaction kay Read-only ni)
            $query = Consultation::with([
                'user:id,name',
                'mechanic:id,user_id,name,shop_name,specialization',
                'mechanic.user:id,name',
                'motorcycle',
                'media',
            ]);

            // 2. Role Filtering
            if ($user->mechanic) {
                $query->where('mechanic_id', $user->mechanic->id);
            } else {
                $query->where('user_id', $user->id);
            }

            // 3. Proper Filters
            // Filter by Status (e.g., ?status=pending)
            $query->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
                // Filter by Type (e.g., ?type=sos)
                ->when($request->type, function ($q, $type) {
                    $q->where('consultation_type', $type);
                })
                // Keyword Search (e.g., ?search=NMAX)
                ->when($request->search, function ($q, $search) {
                    $q->where(function ($innerQuery) use ($search) {
                        // Search sa Issue Description
                        $innerQuery->where('issue_description', 'like', "%{$search}%")
                            // Search sa Motorcycle Table
                            ->orWhereHas('motorcycle', function ($motorcycleQuery) use ($search) {
                            $motorcycleQuery->where('model', 'like', "%{$search}%")
                                ->orWhere('plate_number', 'like', "%{$search}%");
                        });
                    });
                });

            $consultations = $query->latest()->get();

            // if (!$user->mechanic) {
            //     $consultations->each->makeVisible('verification_otp');
            // }

            return response()->json([
                'message' => 'Consultations Retrieved Successfully',
                'count' => $consultations->count(),
                'data' => $consultations,
            ], 200);

        } catch (Exception $e) {
            Log::error('Consultation Index Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to Retrieve Consultations',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */


    public function store(StoreConsultationRequest $request)
    {
        $userId = (int) auth()->id();

        try {
            $result = DB::transaction(function () use ($request, $userId) {
                // Serialize consultation creation for this rider.
                DB::table('users')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first();
                // Recheck motorcycle ownership and availability inside the transaction.
                $motorcycleExists = DB::table('user_motorcycles')
                    ->where('id', $request->input('user_motorcycle_id'))
                    ->where('user_id', $userId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$motorcycleExists) {
                    return [
                        'motorcycle_unavailable' => true,
                    ];
                }


                // Prevent a rider from creating multiple unresolved SOS requests.
                if ($request->input('consultation_type') === 'sos') {
                    $existingSOS = Consultation::findUnresolvedSOS($userId);

                    if ($existingSOS) {
                        return [
                            'duplicate' => true,
                            'data' => [
                                'id' => $existingSOS->id,
                                'status' => $existingSOS->status,
                                'consultation_type' => $existingSOS->consultation_type,
                                'created_at' => $existingSOS->created_at,
                            ],
                        ];
                    }
                }

                $mechanic = Mechanic::query()
                    ->where('is_verified', true)
                    ->where('is_available', true)
                    ->whereHas('user', function ($query) {
                        $query->where('role', 'mechanic')
                            ->where('is_active', true)
                            ->where('is_banned', false);
                    })
                    ->find($request->input('mechanic_id'));

                if (!$mechanic) {
                    return [
                        'ineligible_mechanic' => true,
                    ];
                }

                $consultation = Consultation::create([
                    ...$request->safe()->except(['images', 'videos']),
                    'user_id' => $userId,
                    'agreed_diagnostic_fee' => $mechanic->diagnostic_fee_base,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'verification_otp' => Consultation::generateVerificationOtp(),
                    'verification_otp_created_at' => now(),
                ]);

                $consultation->attachMedia($request);

                return [
                    'duplicate' => false,
                    'consultation' => $consultation,
                ];
            });

            if ($result['motorcycle_unavailable'] ?? false) {
                return response()->json([
                    'message' => 'Selected motorcycle is no longer available. Please refresh your garage and try again.',
                ], 422);
            }

            if ($result['duplicate'] ?? false) {
                return response()->json([
                    'message' => 'You already have an unresolved SOS request. Please check its status before submitting another.',
                    'data' => $result['data'],
                ], 409);
            }
            if ($result['ineligible_mechanic'] ?? false) {
                return response()->json([
                    'message' => 'This mechanic is not verified or is currently unavailable.',
                ], 422);
            }

            $consultation = $result['consultation'];

            return response()->json([
                'message' => 'Consultation Created Successfully',
                'data' => $consultation
                    ->makeVisible('verification_otp')
                    ->load(
                        'media',
                        'mechanic.user:id,name',
                        'motorcycle.brand',
                        'user:id,name'
                    ),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Create Consultation Error', [
                'user_id' => $userId,
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Error Creating Consultation',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : 'Server Error',
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Consultation $consultation)
    {


        $this->authorize('view', $consultation);

        try {
            $consultation->load(['user:id,name', 'mechanic.user:id,name', 'motorcycle', 'media']);
            if ($consultation->user_id === auth()->id()) {
                $consultation->makeVisible('verification_otp');
            }

            return response()->json([
                'message' => 'Consultation Details Retrieved',
                'data' => $consultation,
            ], 200);
        } catch (Exception $e) {
            Log::error("Consultation Show Error [ID: {$consultation->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve Consultation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : ' Server Error'
            ], 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConsultationRequest $request, Consultation $consultation)
    {
        $this->authorize('update', $consultation);

        try {
            $updatedConsultation = DB::transaction(function () use ($request, $consultation) {

                // Lock the consultation to prevent simultaneous status changes.
                $lockedConsultation = Consultation::whereKey($consultation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Recheck authorization against the locked record.
                $this->authorize('update', $lockedConsultation);

                // Completed consultations are permanently read-only.
                if ($lockedConsultation->status === 'completed') {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'status' => 'Completed consultations cannot be modified.',
                    ]);
                }

                $validated = $request->validated();
                $requestedStatus = $validated['status'] ?? null;

                $fields = [];

                // Only allow mechanics to update these service-related fields.
                foreach ([
                    'mechanic_notes',
                    'suggested_parts',
                    'estimated_repair_costs',
                ] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $fields[$field] = $validated[$field];
                    }
                }

                if ($requestedStatus) {

                    // A mechanic can accept only a pending consultation.
                    if ($requestedStatus === 'accepted') {
                        if ($lockedConsultation->status !== 'pending') {
                            throw \Illuminate\Validation\ValidationException::withMessages([
                                'status' => 'Only pending consultations can be accepted.',
                            ]);
                        }
                    }

                    // Service can only start after rider-confirmed QR arrival.
                    elseif ($requestedStatus === 'ongoing') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'status' => 'Service can only start after the rider confirms QR arrival verification.',
                        ]);
                    }

                    $fields['status'] = $requestedStatus;
                }

                $lockedConsultation->update($fields);

                // Preserve existing media attachment functionality.
                $lockedConsultation->attachMedia($request);

                return $lockedConsultation->fresh()
                    ->load('media', 'user:id,name', 'motorcycle');
            });

            return response()->json([
                'message' => 'Consultation Updated Successfully',
                'data' => $updatedConsultation,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;

        } catch (\Throwable $e) {
            Log::error("Consultation Update Error [ID: {$consultation->id}]", [
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Update Failed',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : 'Server Error',
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */


    public function destroy(Request $request, Consultation $consultation)
    {
        // Only the consultation owner can cancel.
        $this->authorize('delete', $consultation);

        $cancelledBy = (int) auth()->id();

        try {
            $result = DB::transaction(function () use ($request, $consultation, $cancelledBy) {
                // Lock the row to prevent simultaneous status changes.
                $lockedConsultation = Consultation::whereKey($consultation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Recheck status after acquiring the lock.
                if (!$lockedConsultation->isCancellable()) {
                    return [
                        'cancelled' => false,
                        'consultation' => $lockedConsultation,
                    ];
                }

                $reason = trim((string) $request->input('reason', ''));

                // Accepted and ongoing cancellations require a reason.
                if (
                    $lockedConsultation->status !== 'pending' &&
                    (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000)
                ) {
                    return [
                        'invalid_reason' => true,
                    ];
                }

                $lockedConsultation->cancel(
                    $cancelledBy,
                    $reason
                );

                return [
                    'cancelled' => true,
                    'status' => $lockedConsultation->status,
                    'consultation' => $lockedConsultation,
                ];
            });

            if ($result['invalid_reason'] ?? false) {
                return response()->json([
                    'message' => 'Please provide a cancellation reason between 10 and 1000 characters.',
                ], 422);
            }

            if (!$result['cancelled']) {
                return response()->json([
                    'message' => 'Cannot cancel consultation. It is already '
                        . $result['consultation']->status,
                ], 409);
            }

            $isPending = $result['status'] === 'pending';

            return response()->json([
                'message' => $isPending
                    ? 'Consultation Successfully Deleted'
                    : 'Consultation Successfully Cancelled',
                'data' => $result['consultation'],
            ], 200);

        } catch (Exception $e) {
            Log::error(
                "Consultation Cancellation Error[{$consultation->id}]: "
                . $e->getMessage()
            );

            return response()->json([
                'message' => 'Failed to Cancel Consultation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
    }

    public function updateLocation(
        UpdateConsultationLocationRequest $request,
        Consultation $consultation
    ) {
        $this->authorize('updateLocation', $consultation);

        try {
            $updatedConsultation = DB::transaction(function () use ($request, $consultation) {

                // Lock consultation to prevent simultaneous status/location changes.
                $lockedConsultation = Consultation::whereKey($consultation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Recheck ownership and status against the locked record.
                $this->authorize('updateLocation', $lockedConsultation);

                $validated = $request->validated();

                $latitude = round((float) $validated['latitude'], 8);
                $longitude = round((float) $validated['longitude'], 8);
                $now = now();

                // Check if an unconfirmed QR is still active.
                $hasActiveQr = $lockedConsultation->qrChallenges()
                    ->whereNull('confirmed_at')
                    ->whereNull('revoked_at')
                    ->where(function ($query) use ($now) {
                        $query->where(function ($query) use ($now) {
                            $query->whereNull('scanned_at')
                                ->where('expires_at', '>', $now);
                        })->orWhere(function ($query) use ($now) {
                            $query->whereNotNull('scanned_at')
                                ->where('confirmation_expires_at', '>', $now);
                        });
                    })
                    ->exists();

                $oldLatitude = $lockedConsultation->latitude;
                $oldLongitude = $lockedConsultation->longitude;

                $shouldRevokeQr = false;
                $keepOriginalCoordinates = false;

                if ($hasActiveQr) {
                    if ($oldLatitude === null || $oldLongitude === null) {
                        // No reliable reference coordinates.
                        $shouldRevokeQr = true;
                    } else {
                        $distance = $this->calculateDistanceInMeters(
                            (float) $oldLatitude,
                            (float) $oldLongitude,
                            $latitude,
                            $longitude
                        );

                        if ($distance > 50) {
                            $shouldRevokeQr = true;
                        } else {
                            // Preserve original coordinates to prevent cumulative GPS drift.
                            $keepOriginalCoordinates = true;
                        }
                    }
                }

                $lockedConsultation->update([
                    'latitude' => $keepOriginalCoordinates
                        ? $oldLatitude
                        : $latitude,
                    'longitude' => $keepOriginalCoordinates
                        ? $oldLongitude
                        : $longitude,
                    'location_name' => array_key_exists('location_name', $validated)
                        ? $validated['location_name']
                        : $lockedConsultation->location_name,
                ]);

                // Revoke unconfirmed QR challenges when movement exceeds 50 meters.
                if ($shouldRevokeQr) {
                    $lockedConsultation->qrChallenges()
                        ->whereNull('confirmed_at')
                        ->whereNull('revoked_at')
                        ->update([
                            'revoked_at' => $now,
                            'updated_at' => $now,
                        ]);
                }

                return $lockedConsultation->fresh();
            });

            return response()->json([
                'message' => 'Breakdown location updated successfully.',
                'data' => $updatedConsultation,
            ], 200);

        } catch (AuthorizationException $e) {
            throw $e;

        } catch (\Throwable $e) {
            Log::error("Consultation Location Update Error [ID: {$consultation->id}]", [
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Location update failed.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : 'Server Error',
            ], 500);
        }
    }

    public function complete(Request $request, Consultation $consultation)
    {
        $this->authorize('update', $consultation);

        try {
            $result = DB::transaction(function () use ($request, $consultation) {
                $lockedConsultation = Consultation::whereKey($consultation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Recheck authorization after acquiring the lock.
                $this->authorize('update', $lockedConsultation);

                $user = $request->user();
                $mechanic = $user->mechanic;

                // Confirm that the mechanic account is eligible.
                if (
                    !$user->is_active ||
                    $user->is_banned ||
                    !$mechanic ||
                    !$mechanic->is_verified
                ) {
                    return [
                        'completed' => false,
                        'status' => 403,
                        'message' => 'Your account is not authorized to complete consultations.',
                    ];
                }

                // Prevent duplicate or invalid completion.
                if ($lockedConsultation->status === 'completed') {
                    return [
                        'completed' => false,
                        'status' => 409,
                        'message' => 'This consultation has already been completed.',
                    ];
                }

                if ($lockedConsultation->status !== 'ongoing') {
                    return [
                        'completed' => false,
                        'status' => 422,
                        'message' => 'Only ongoing consultations can be completed.',
                    ];
                }

                // Confirm that the motorcycle still belongs to the rider.
                $motorcycleBelongsToRider = $lockedConsultation->motorcycle()
                    ->where('user_id', $lockedConsultation->user_id)
                    ->exists();

                if (!$motorcycleBelongsToRider) {
                    return [
                        'completed' => false,
                        'status' => 422,
                        'message' => 'The consultation motorcycle does not belong to the rider.',
                    ];
                }

                // Require at least one valid, mechanic-verified service record.
                $hasVerifiedMaintenanceLog = $lockedConsultation->maintenanceLogs()
                    ->where('mechanic_id', $lockedConsultation->mechanic_id)
                    ->where('user_motorcycle_id', $lockedConsultation->user_motorcycle_id)
                    ->where('is_verified_by_mechanic', true)
                    ->exists();

                if (!$hasVerifiedMaintenanceLog) {
                    return [
                        'completed' => false,
                        'status' => 422,
                        'message' => 'A verified maintenance log is required before completing this consultation.',
                    ];
                }

                // Complete the service without changing payment status.
                $lockedConsultation->update([
                    'status' => 'completed',
                ]);

                return [
                    'completed' => true,
                    'consultation' => $lockedConsultation->fresh(),
                ];
            });

            if (!$result['completed']) {
                return response()->json([
                    'message' => $result['message'],
                ], $result['status']);
            }

            return response()->json([
                'message' => 'Consultation completed successfully.',
                'data' => $result['consultation'],
            ], 200);

        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to complete this consultation.',
            ], 403);

        } catch (Exception $e) {
            Log::error(
                "Consultation Completion Error[{$consultation->id}]: "
                . $e->getMessage()
            );

            return response()->json([
                'message' => 'Failed to complete consultation.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
    }


    public function mechanicRequests()
    {
        try {
            $mechanic = auth()->user()->mechanic;

            if (!$mechanic) {
                return response()->json([
                    'message' => 'Unauthorized, You are not a Mechanic'
                ], 403);
            }
            $consultations = Consultation::where('mechanic_id', $mechanic->id)
                ->with(['user:id,name', 'motorcycle.brand', 'media'])
                ->latest()->get();

            return response()->json([
                'message' => 'Mechanic Job Board Retrieved',
                'count' => $consultations->count(),
                'data' => $consultations
            ]);
        } catch (Exception $e) {
            Log::error(" Mechanic Index Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server Error, Please come back later',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
    /**
     * Calculate the distance between two GPS coordinates in meters.
     */
    private function calculateDistanceInMeters(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000; // Earth's average radius in meters.

        $latDifference = deg2rad($lat2 - $lat1);
        $lonDifference = deg2rad($lon2 - $lon1);

        $a = sin($latDifference / 2) ** 2
            + cos(deg2rad($lat1))
            * cos(deg2rad($lat2))
            * sin($lonDifference / 2) ** 2;

        // Prevent floating-point precision issues.
        $a = min(1, max(0, $a));

        $centralAngle = 2 * atan2(
            sqrt($a),
            sqrt(1 - $a)
        );

        return $earthRadius * $centralAngle;
    }
}
