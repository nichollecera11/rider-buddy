<?php

namespace App\Http\Controllers;

use App\Models\SellerMedia;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Seller;

class SellerMediaController extends Controller
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
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $seller = Seller::findOrFail($id);
        if ($seller->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        DB::beginTransaction();
        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                //Himoerns Unique Name
                $filename = time() . '_' . $file->getClientOriginalName();
                //salbar de public seller_media folder 
                $path = $file->storeAs('seller_media', $filename, 'public');

                SellerMedia::create([
                    'seller_id' => $seller->id,
                    'file_path' => $path,
                    'type' => $file->getClientMimeType()
                ]);
                DB::commit();
                return response()->json([
                    'message' => 'Media Uploaded Successfully'
                ], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
        //Deleterns una ang file sa DB para dili kalas ug utok
        if (isset($path) && Storage::disk('public')->exists($path)){
            Storage::disk('public')->delete($path);
        }
            Log::error("Upload Failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to Upload Seller Media',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(SellerMedia $sellerMedia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SellerMedia $sellerMedia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SellerMedia $sellerMedia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SellerMedia $sellerMedia)
    {
        if ($sellerMedia->seller->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        $filePath = $sellerMedia->file_path;

        DB::beginTransaction();
        try {
            $sellerMedia->delete();
            DB::commit();

            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            return response()->json([
                'message' => 'Seller Media Deleted Successfully'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Seller Media Delete Failed [ID: {$sellerMedia->id}] " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to Delete Seller Media',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}
