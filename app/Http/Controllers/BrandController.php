<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Brand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Str;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $brands = Brand::select('id', 'name', 'slug')->orderBy('name', 'asc')->get();
            return response()->json([
                'status' => 'success',
                'count' => $brands->count(),
                'data' => $brands
            ], 200);
        } catch (Exception $e) {
            Log::error("Brand Index Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch brands'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Unauthorized, Contact Administrator');

        $request->validate([
            'name' => 'required|string|unique:brands,name|max:255',
        ]);

        DB::beginTransaction();
        try {
            $brand = Brand::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name)
            ]);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Brand created Successfully',
                'data' => $brand
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Brand Store Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to Create Brand',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
        return response()->json([
            'status' => 'success',
            'data' => $brand,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Unauthorized');
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name, ' . $brand->id,
        ]);
        DB::beginTransaction();
        try {
            $brand->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name)
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Brand Updated Successfully',
                'data' => $brand
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Brand Update Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Brand Update Failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Unauthorized');
        DB::beginTransaction();
        try {
            $brand->delete();
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Brand Deleted Successfully'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Brand Delete Failed: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Brand Delete Failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}
