<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Mechanic;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            $query = Consultation::with(['user', 'mechanic.user', 'motorcycle', 'media']);

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
    public function store(Request $request)
    {
        $fields = $request->validate([
            'mechanic_id' => 'required|exists:mechanics,id',
            'user_motorcycle_id' => 'required|exists:user_motorcycles,id',
            'issue_description' => 'required|string|min:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string',
            'consultation_type' => 'required|in:standard,sos',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos' => 'sometimes|array',
            'videos.*' => 'mimes:mp4,mov,avi|max:20480',
        ]);
        DB::beginTransaction();

        $userId = auth()->id();

        try {
            $mechanic = Mechanic::findOrFail($fields['mechanic_id']);
            $diagnosticFee = $mechanic->diagnostic_fee_base;

            //otp
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $consultation = Consultation::create([
                'user_id' => $userId,
                'mechanic_id' => $fields['mechanic_id'],
                'user_motorcycle_id' => $fields['user_motorcycle_id'],
                'consultation_type' => $fields['consultation_type'],
                'issue_description' => $fields['issue_description'],
                'agreed_diagnostic_fee' => $diagnosticFee,
                'status' => 'pending',
                'payment_status' => 'pending',
                'latitude' => $fields['latitude'] ?? null,
                'longitude' => $fields['longitude'] ?? null,
                'location_name' => $fields['location_name'] ?? null,
                'verification_otp' => $otp,
            ]);

            $mediaTypes = ['images' => 'image', 'videos' => 'video'];

            foreach ($mediaTypes as $inputName => $type) {
                if ($request->hasFile($inputName)) {
                    foreach ($request->file($inputName) as $file) {
                        $path = $file->store('consultations/media', 'public');
                        //Model!
                        $consultation->media()->create([
                            'file_path' => $path,
                            'file_type' => $type
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Consultation Created Successfully',
                'data' => $consultation->load('media', 'mechanic.user', 'motorcycle.brand', 'user')
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Create Consultation Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Error creating consultation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Consultation $consultation)
    {
        $user = auth()->user();

        try {
            $isOwner = $consultation->user_id === $user->id;
            $isAssignedMechanic = $user->mechanic && $consultation->mechanic_id === $user->mechanic->id;

            if (!$isOwner && !$isAssignedMechanic) {
                return response()->json([
                    'message' => 'Unauthorized. You do not have access to this consultation'
                ], 403);
            }

            //Eager Loading using of Model Binding Technique HAHAHA
            $consultation->load(['user', 'mechanic.user', 'motorcycle', 'media']);

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
    public function update(Request $request, $id)
    {
        $consultation = Consultation::findOrFail($id);
        $user = auth()->user();

        $mechanicProfile = $user->mechanic;

        // 2. Check kon Mechanic ba gyud siya (kay basin Rider ra siya nga nanghilabot)
        if (!$mechanicProfile || $consultation->mechanic_id !== $mechanicProfile->id) {
            return response()->json(['message' => 'Unauthorized. This is not your assigned job.'], 403);
        }

        $fields = request()->validate([
            // 'sometimes' nagpasabot nga i-validate ra ni kon naa ang field sa request
            'issue_description' => 'sometimes|string|min:10',
            'consultation_type' => 'sometimes|in:standard,sos',

            // Kani ang mga fields nga sagad i-update sa Mekaniko
            'status' => 'sometimes|in:pending,accepted,ongoing,completed,cancelled',
            'payment_status' => 'sometimes|in:pending,paid,failed',

            // Para sa Trust Ecosystem: Diagnosis & Quote
            'mechanic_notes' => 'sometimes|nullable|string',
            'suggested_parts' => 'sometimes|nullable|array', // JSON array ni puhon
            'estimated_repair_costs' => 'sometimes|nullable|numeric',

            // Security: Verification OTP inig abot sa mekaniko
            'verification_otp_input' => 'sometimes|string|size:6',

            // Location updates (kon mabalhin ang motor)
            'latitude' => 'sometimes|nullable|numeric',
            'longitude' => 'sometimes|nullable|numeric',
            'location_name' => 'sometimes|nullable|string',

            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos' => 'sometimes|array',
            'videos.*' => 'mimes:mp4,mov,avi|max:20480',
        ]);

        DB::beginTransaction();
        try {
            // 🚀 TRUST LOGIC: OTP Verification
            // Kon i-set ang status to 'ongoing', dapat match ang OTP gikan ni Rider
            if (isset($fields['status']) && $fields['status'] === 'ongoing') {

                if (!isset($fields['verification_otp_input'])) {
                    return response()->json([
                        'message' => 'QR Scan required to start service'
                    ], 422);
                }
                if ($fields['verification_otp_input'] !== $consultation->verification_otp) {
                    return response()->json(['message' => 'Invalid OTP Code. Verification failed.'], 422);
                }
                $otpAge = $consultation->updated_at->diffinMinutes(now());
                if ($otpAge > 2) {
                    return response()->json([
                        'message' => 'QR Code Expired, Rider needs to refresh the QR'
                    ], 422);
                }
                if (!$consultation->arrived_at) {
                    $consultation->arrived_at = now(); // Record arrival time
                }
            }

            // 2. The Actual Update
            unset($fields['verification_otp_input']);
            $consultation->update($fields);

            // 3. Media Upload (Kon naay dugang images/video ang mekaniko inig diagnosis)
            $mediaTypes = ['images' => 'image', 'videos' => 'video'];
            foreach ($mediaTypes as $inputName => $type) {
                if ($request->hasFile($inputName)) {
                    foreach ($request->file($inputName) as $file) {
                        $path = $file->store('consultations/media', 'public');
                        $consultation->media()->create([
                            'file_path' => $path,
                            'file_type' => $type
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Consultation Updated Successfully',
                'data' => $consultation->load('media', 'user', 'motorcycle')
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Consultation Update Error" . $e->getMessage());
            return response()->json([
                'message' => 'Update Failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $consultation = Consultation::findOrFail($id);
        $userId = auth()->id();
        if ($consultation->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized. You do not own this request'], 403);
        }
        if ($consultation->status !== 'pending') {
            return response()->json(['message' => 'Cannot cancel consultation. It is already ' . $consultation->status], 402);
        }

        DB::beginTransaction();

        try {
            foreach ($consultation->media as $media) {
                if (Storage::disk('public')->exists($media->file_path)) {
                    Storage::disk('public')->delete($media->file_path);
                }
            }
            $consultation->media()->delete();
            $consultation->delete();
            DB::commit();
            return response()->json(['message' => 'Consultation Successfully Deleted', 'data' => $consultation]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Consultation Delete Error[{$id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to Cancel Consultation',
                'error' => env('APP_DEBUG') ? $e->getmessage() : 'Server Error'
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
                ->with(['user', 'motorcycle.brand', 'media'])
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
}
