<?php

namespace App\Http\Controllers;

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
    public function store(Request $request, $motorcycle_id)
    {
        $user_motorcycle = UserMotorcycle::where('id', $motorcycle_id)->where('user_id', auth()->id())->first();

        if (!$user_motorcycle) {
            return response()->json([
                'message' => 'Motorcycle not found'
            ], 404);
        }
        $fields = $request->validate([
            // Kinahanglan unique ni sila sa l_t_o_compliances table para anti-fraud
            'plate_number' => 'required|string|unique:l_t_o_compliances,plate_number',
            'engine_number' => 'required|string|unique:l_t_o_compliances,engine_number',
            'chassis_number' => 'required|string|unique:l_t_o_compliances,chassis_number',
            'registration_expiry' => 'required|date|after:today',

            // Ang OR/CR photo/file
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();

        try {


            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();

                $path = $file->storeAs('lto_docs', $filename, 'private');

                $lto = LTOCompliance::create([
                    'user_motorcycle_id' => $user_motorcycle->id,
                    'plate_number' => $fields['plate_number'],
                    'engine_number' => $fields['engine_number'],
                    'chassis_number' => $fields['chassis_number'],
                    'registration_expiry' => $fields['registration_expiry'],
                    'status' => 'pending',
                ]);

                $lto->media->create([
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
    public function show(LTOCompliance $lTOCompliance)
    {
        //
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

    public function showImage($id)
    {
        $lto = LTOCompliance::with('user_motorcycle')->findOrFail($id);
        $user = auth()->user();
        //strict security
        $isOwner = $user->id === $lto->user_motorcycle->user_id;
        $isAdmin = $user->role === 'admin';

        if (!$isOwner && !$isAdmin) {
            Log::warning("Unauthorized attempt on LTO image ID: {$id} by User ID:" . auth()->id());
            return response()->json([
                'message' => 'Unauthorized Access'
            ], 403);
        }
        if (!$lto->file_path || !Storage::disk('private')->exists($lto->file_path)) {
            return response()->json([
                'message' => 'Image not Found'
            ], 404);
        }
        $path = Storage::disk('private')->path($lto->file_path);
        return response()->file($path);

        if (!$lto->file_path || !Storage::disk('private')->exists($lto->file_path)) {
            return response()->json([
                'message' => 'Image Not Found'
            ], 404);
        }
        $path = Storage::disk('private')->path($lto->file_path);
        return response()->file($path);
    }

    //Admin Verification

    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|nullable',
            'remarks' => 'string|nullable'
        ]);

        DB::beginTransaction();
        try {
            $lto = LTOCompliance::findOrFail($id);
            $lto->update([
                'status' => $request->status,
                'rejection_reason' => $request->status === 'rejected' ? $request->rejection_reason : null,
                'remarks' => $request->remarks,
                'verified_by' => auth()->id(), //admin na nag verify
                'verified_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => "LTO Compliance status updated to {$request->status}.",
                'data' => $lto
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'LTO Record not found'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Admin Verification Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong while updating the status.',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function listpending()
    {
        $pending = LTOCompliance::with('user_motorcycle.user')->where('status', 'pending')->get();

        return response()->json($pending);
    }
}
