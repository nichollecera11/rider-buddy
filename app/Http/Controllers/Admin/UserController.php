<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Every route here sits inside the admin group (auth:sanctum + not.banned + admin).
    // The Policy adds the rules the middleware cannot express, like "not yourself".

    public function index(Request $request)
    {
        try {
            $users = User::query()
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->search;
                    $q->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('role'), fn($q) => $q->where('role', $request->role))
                ->when($request->status === 'banned', fn($q) => $q->where('is_banned', true))
                ->when($request->status === 'active', fn($q) => $q->where('is_banned', false)->where('is_active', true))
                ->latest('id')
                ->paginate(12);

            return response()->json([
                'message' => 'Users retrieved successfully',
                'data' => $users
            ], 200);
        } catch (Exception $e) {
            Log::error("Admin User Index Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve users',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function show(User $user)
    {
        try {
            $user->load(['mechanic', 'seller']);

            return response()->json([
                'message' => 'User retrieved successfully',
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error("Admin User Show Error [ID: {$user->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve user',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $fields = $request->validated();

        // An admin must not lock themselves out by demoting their own account
        if (isset($fields['role']) && $fields['role'] !== $user->role && $user->is(auth()->user())) {
            return response()->json([
                'message' => 'You cannot change your own role'
            ], 422);
        }

        try {
            $user->fill(collect($fields)->only(['name', 'email'])->all());

            // role is not mass-assignable, so it is set on purpose, never through fill()
            if (isset($fields['role'])) {
                $user->role = $fields['role'];
            }

            $user->save();

            return response()->json([
                'message' => 'User updated successfully',
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error("Admin User Update Error [ID: {$user->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'User update failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function ban(BanUserRequest $request, User $user)
    {
        $this->authorize('ban', $user);

        try {
            // Set on purpose: is_banned and ban_reason are not mass-assignable
            $user->is_banned = true;
            $user->ban_reason = $request->validated()['ban_reason'] ?? 'Banned by administrator';
            $user->save();

            // Sign them out right away (the not.banned middleware would also catch them on their next request)
            $user->tokens()->delete();

            return response()->json([
                'message' => 'User banned and active sessions revoked',
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error("Admin User Ban Error [ID: {$user->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'User ban failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function unban(User $user)
    {
        $this->authorize('ban', $user);

        try {
            $user->is_banned = false;
            $user->ban_reason = null;
            $user->save();

            return response()->json([
                'message' => 'User unbanned',
                'data' => $user
            ], 200);
        } catch (Exception $e) {
            Log::error("Admin User Unban Error [ID: {$user->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'User unban failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Deleting a user cascades into their profiles and can erase consultation and health history.
        // Accounts with history are banned instead of deleted.
        if ($user->hasHistory()) {
            return response()->json([
                'message' => 'This user has history (consultations or motorcycles) and cannot be deleted. Ban the account instead.'
            ], 409);
        }

        try {
            $user->tokens()->delete();
            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error("Admin User Delete Error [ID: {$user->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'User delete failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}