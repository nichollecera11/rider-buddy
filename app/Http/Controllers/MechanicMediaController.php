<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\MechanicMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Mechanic;


class MechanicMediaController extends Controller
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
    public function store(Request $request, $id)
    {
        $request->validate([
            'mechanic_id' => 'required|exists:mechanics,id',
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);
        //validation
        $MechanicMedia = Mechanic::findOrFail($id);
        if ($MechanicMedia->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        DB::beginTransaction();
        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('mechanic_media', $filename, 'public');

                MechanicMedia::create([
                    'mechanic_id' => $request->mechanic_id,
                    'file_path' => $path,
                    'type' => $file->getClientMimeType()
                ]);
                DB::commit();
                return response()->json([
                    'message' => 'Media Uploaded Succesfully'
                ], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            Log::error("Upload Failed:" . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to Upload Mechanic Media',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MechanicMedia $mechanicMedia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MechanicMedia $mechanicMedia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MechanicMedia $mechanicMedia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MechanicMedia $mechanicMedia)
    {
        if ($mechanicMedia->mechanic->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        $filePath = $mechanicMedia->file_path;

        DB::beginTransaction();
        try {
            $mechanicMedia->delete();
            DB::commit();

            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return response()->json([
                'message' => 'Mechanic Media Successfully Deleted'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Mechanic Media Delete Failed: [{$mechanicMedia->id}]" . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to Delete Mechanic Media',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}
