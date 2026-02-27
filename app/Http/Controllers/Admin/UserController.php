<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }
        // Role Filter
        if ($request->has('role')) {
            $query->where('role', $request->role);
        } 
        //Banned Logic
        if ($request->has('status')) {
            if ($request->status == 'banned') {
                $query->where('is_banned', true);
            } elseif ($request->status == 'active') {
                $query->where('is_banned', false)->where('is_active', true);
            }
        }
        return response()->json([
            'status' => 'success',
            'count' => $query->count(),
            'data' => $query->latest()->paginate(12)
        ]);

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
    public function show(User $user)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'User Updated Successfully',
            'data' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'role' => 'sometimes|string|in:admin,rider,mechanic,seller',
                'is_active' => 'sometimes|boolean', 
                'is_banned' => 'sometimes|boolean', 
            ]);

            $user->update($validated);
            DB::commit();
            return response()->json([
                'status' => 'Success',
                'message' => 'User Details Retrieved',
                'data' => $user
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'User Update Failed',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        DB::beginTransaction();
        try {
            // Dinhi, kon naay related data, i-delete pud (e.g., reviews)
            // $user->reviews()->delete();
            $user->delete();
            DB::commit();
            return response()->json([
                'status' => 'Success',
                'message' => 'User Deleted Permanently',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'User Delete Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function ban(User $user)
    {
        $user->update(['is_banned' => true]);
        return response()->json(['message' => 'User has been banned!']);
    }
}