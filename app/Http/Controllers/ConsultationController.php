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
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'mechanic_id' => 'required|exists:mechanics,id',
            'motorcycle_id' => 'required|exists:motorcycles,id',
            'issue_description' => 'required|string|min:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_name' => 'nullable|string',
            'consultation_type' => 'required|in:standard,sos',
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
                'motorcycle_id' => $fields['motorcycle_id'],
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
                'data' => $mechanic->load('media', 'mechanic', 'motorcycle')
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Create Consultation Error" . $e->getMessage());
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Consultation $consultation)
    {
        //
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
        ]);

        DB::beginTransaction();
        try {
            // 🚀 TRUST LOGIC: OTP Verification
            // Kon i-set ang status to 'ongoing', dapat match ang OTP gikan ni Rider
            if (isset($fields['status']) && $fields['status'] === 'ongoing') {
                if ($fields['verification_otp_input'] !== $consultation->verification_otp) {
                    return response()->json(['message' => 'Invalid OTP Code. Verification failed.'], 422);
                }
                $consultation->arrived_at = now(); // Record arrival time
            }

            // 2. The Actual Update
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
    public function destroy(Consultation $consultation)
    {
        //
    }
}
