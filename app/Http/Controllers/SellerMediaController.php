<?php

namespace App\Http\Controllers;

use App\Models\SellerMedia;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public function store(Request $request)
    {
        //
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
        if ($sellerMedia->seller->user_id !== auth()->id()){
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        DB::beginTransaction();
        try {
            if (Storage::disk('public')->exists($sellerMedia->file_path)){
                Storage::disk('public')->delete($sellerMedia->file_path);
            }

            $sellerMedia->delete();
            DB::commit();
            return response()->json([
                'message' => 'Seller Media Deleted Successfully'
            ], 200);
        }catch (Exception $e){
            DB::rollBack();
            Log::error("Seller Media Delete Failed [ID: {$sellerMedia->id}] " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Deleting Seller Data and Media Failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}
