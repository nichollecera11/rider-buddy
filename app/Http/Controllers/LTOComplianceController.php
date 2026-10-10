<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLTOComplianceRequest;
use App\Http\Requests\VerifyLTOComplianceRequest;
use App\Models\LTOCompliance;
use App\Models\UserMotorcycle;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LTOComplianceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $records = LTOCompliance::with('user_motorcycle.brand')
                ->whereHas('user_motorcycle', function ($q) {
                    $q->where('user_id', auth()->id());
                })
                ->latest()
                ->get();

            return response()->json([
                'message' => 'LTO records retrieved successfully',
                'count' => $records->count(),
                'data' => $records
            ], 200);
        } catch (Exception $e) {
            Log::error("LTO Index Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve LTO records',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
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
    public function store(StoreLTOComplianceRequest $request, UserMotorcycle $userMotorcycle)
    {
        $this->authorize('create', [LTOCompliance::class, $userMotorcycle]);
        $fields = $request->validated();
        $path = null;

        DB::beginTransaction();

        try {


            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();

                $path = $request->file('file')->store('lto_docs', 'private');

                $lto = LTOCompliance::create([
                    'user_motorcycle_id' => $userMotorcycle->id,
                    'plate_number' => $fields['plate_number'],
                    'engine_number' => $fields['engine_number'],
                    'chassis_number' => $fields['chassis_number'],
                    'registration_expiry' => $fields['registration_expiry'],
                    'status' => 'pending',
                ]);

                $lto->media()->create([
                    'file_path' => $path,
                    'document_type' => 'OR_CR',
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'LTO Documents Submitted for Verification'
                ], 201);



            }
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($path) && Storage::disk('private')->exists($path)) {
                Storage::disk('private')->delete($path);
            }
            return response()->json([
                'message' => 'LTO Documents Submission Failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LTOCompliance $ltoCompliance)
    {
        $this->authorize('view', $ltoCompliance);

        try {
            $ltoCompliance->load('user_motorcycle.brand');

            return response()->json([
                'message' => 'LTO record retrieved successfully',
                'data' => $ltoCompliance
            ], 200);
        } catch (Exception $e) {
            Log::error("LTO Show Error [ID: {$ltoCompliance->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve LTO record',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LTOCompliance $lTOCompliance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LTOCompliance $lTOCompliance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LTOCompliance $lTOCompliance)
    {
        //
    }

    public function showImage(LTOCompliance $ltoCompliance)
    {
        $this->authorize('view', $ltoCompliance);

        $media = $ltoCompliance->media()->where('document_type', 'OR_CR')->first();

        if (!$media || !Storage::disk('private')->exists($media->file_path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        return response()->file(Storage::disk('private')->path($media->file_path));
    }

    //Admin Verification

    public function verify(VerifyLTOComplianceRequest $request, LTOCompliance $ltoCompliance)
    {
        $this->authorize('verify', $ltoCompliance);

        if (!$ltoCompliance->isPending()) {
            return response()->json([
                'message' => 'This LTO Record was already ' . $ltoCompliance->status
            ], 409);
        }

        DB::beginTransaction();
        try {
            $fields = $request->validated();

            $ltoCompliance->update([
                'status' => $fields['status'],
                'rejection_reason' => $fields['status'] === 'rejected' ? $request->rejection_reason : null,
                'remarks' => $fields['remarks'] ?? null,
                'verified_by' => auth()->id(), //admin na nag verify
                'verified_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => "LTO Compliance status updated to {$fields['status']}.",
                'data' => $ltoCompliance
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("LTO Verify Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Something went wrong while updating the status.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function listpending()
    {
        $this->authorize('viewAny', LTOCompliance::class);

        $pending = LTOCompliance::with('user_motorcycle.user')
            ->where('status', 'pending')
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => 'Pending LTO records retrieved',
            'data' => $pending
        ], 200);
    }

}
