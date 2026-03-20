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

        } catch (Exception $e) {
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
        abort_if(auth()->user()->role !== 'admin', 403, 'Unauthorized');
        $request->validate([
            'name' => 'required|string|unique:categories,name|max:255',
        ]);

        DB::beginTransaction();
        try {
            $category = Category::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name)
            ]);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Category Created  Successfully'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Category Create Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Category Save Failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json([
            'status' => 'success',
            'data' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        abort_if(auth()->user()->role !== 'admin', 403, 'Unauthorized');
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name, ' . $category->id,
        ]);
        DB::beginTransaction();
        try{
            $category->update([
                'name'=> $request->name,
                'slug' => Str::slug($request->name)
            ]);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Category Updated Successfully',
                'data' => $category
            ], 201);
        }catch (Exception $e){
            Db::rollBack();
            Log::error("Category Update Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Category Update Failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
