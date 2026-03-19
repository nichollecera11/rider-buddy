<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $categories = Category::select('id', 'name', 'slug', 'description')->orderBy('name', 'asc')->get();
            return response()->json([
                'status' => 'success',
                'count' => $categories->count(),
                'data' => $categories
            ], 200);

        }catch (Exception $e){
            Log::error("Category Index Error:" . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to Fetch Category'
            ], 500);
        }
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
        //
    }
}
