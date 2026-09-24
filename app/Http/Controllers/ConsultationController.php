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
    public function store(StoreConsultationRequest $request)
    {
        $mechanic = Mechanic::findOrFail($request->mechanic_id);

        DB::beginTransaction();

        try {

            $consultation = Consultation::create([
                ...$request->safe()->except(['images', 'videos']),
                'user_id' => auth()->id(),
                'agreed_diagnostic_fee' => $mechanic->diagnostic_fee_base,
                'status' => 'pending',
                'payment_status' => 'pending',
                'verification_otp' => str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT),
            ]);

            $consultation->attachMedia($request);

            DB::commit();
            return response()->json([
                'message' => 'Consultation Created Successfully',
                'data' => $consultation->load('media', 'mechanic.user', 'motorcycle.brand', 'user'),
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Create Consultation Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Error Creating Consultation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error',
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Consultation $consultation)
    {


        $this->authorize('view', $consultation);

        try {
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
    public function update(UpdateConsultationRequest $request, Consultation $consultation)
    {
        $this->authorize('update', $consultation);

        DB::beginTransaction();
        try {
            $fields = $request->safe()->except('verification_otp_input');

            if ($request->status === 'ongoing') {
                if (!$request->filled('verification_otp_input')) {
                    return response()->json(['message' => 'QR Scan required to start service'], 422);
                }

                [$ok, $error] = $consultation->verifyOtp($request->verification_otp_input);
                if (!$ok) {
                    return response()->json(['message' => $error], 422);
                }

                if (!$consultation->arrived_at) {
                    $fields['arrived_at'] = now();
                }
            }

            $consultation->update($fields);
            $consultation->attachMedia($request);

            DB::commit();

            return response()->json([
                'message' => 'Consultation Updated Successfully',
                'data' => $consultation->load('media', 'user', 'motorcycle'),
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Consultation Update Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Update Failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error',
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Consultation $consultation)
    {
        $this->authorize('delete', '$consultation');

        if (!$consultation->isCancellable()) {
            return response()->json([
                'message' => 'Cannot cancel consultation. It is already' . $consultation->status
            ], 409);
        }
        try {
            $consultation->cancel();
            return response()->json([
                'message' => 'Consultation Succesfully Deleted',
                'data' => $consultation
            ], 200);
        } catch (Exception $e) {
            Log::error("Consultation Delete Error[{$consultation->id}]:" . $e->getMessage());
            return response()->json([
                'message' => 'Failed to Cancel Consultation',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
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
