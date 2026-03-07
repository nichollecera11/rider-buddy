<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ConsultationMedia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ConsultationMediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {


        $media = ConsultationMedia::findOrFail($id);
        $user = auth()->user();

        if ($media->consultation->user_id !== $user->id && $media->consultation->mechanic_id !== $user->mechanic?->id) {
            return response()->json([
                'message' => 'Unauthorized, Limited Permissions'
            ], 403);
        }

        DB::beginTransaction();
        try {

            if (Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }

            $media->delete();

            DB::commit();

            return response()->json([
                'message' => 'Media Deleted and Files Removed From Storage'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Deleting Media Files Failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Deleting Media and Files Failed, Please Try Again',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}
