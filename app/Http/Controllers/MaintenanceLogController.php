<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceLogRequest;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Consultation;


class MaintenanceLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        DB::beginTransaction();
        try {
            $query = MaintenanceLog::with(['motorcycle.brand', 'mechanic.user:id,name']);

            if ($user->mechanic) {
                $query->where('mechanic_id', $user->mechanic->id);
            } else {
                $query->whereHas('motorcycle', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }
            $logs = $query->latest()->get();

            DB::commit();
            return response()->json([
                'message' => 'Maintenance Logs Retrieved Successfully',
                'count' => $logs->count(),
                'data' => $logs
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Maintenance Log Retrieve Failed:" . $e->getMessage());
            return response()->json([
                'message' => 'Failed to Retrieve Maintenance Log',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }

    }

    public function store(StoreMaintenanceLogRequest $request)
    {
        $this->authorize('create', MaintenanceLog::class);

        $fields = $request->validated();

        return DB::transaction(function () use ($request, $fields) {

            $user = $request->user();
            $mechanic = $user->mechanic;

            // Confirm that the account and mechanic profile are eligible.
            if (
                !$user->isMechanic() ||
                !$mechanic ||
                !$mechanic->is_verified ||
                !$user->is_active ||
                $user->is_banned
            ) {
                return response()->json([
                    'message' => 'Your account is not authorized to create maintenance records.'
                ], 403);
            }

            // Lock the consultation while checking and creating its maintenance log.
            $consultation = Consultation::whereKey($fields['consultation_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Only the mechanic assigned to this consultation can record the service.
            if ($consultation->mechanic_id !== $mechanic->id) {
                return response()->json([
                    'message' => 'You are not assigned to this consultation.'
                ], 403);
            }

            // Service records can only be added while the consultation is ongoing.
            if ($consultation->status !== 'ongoing') {
                return response()->json([
                    'message' => 'The consultation must be ongoing before adding a maintenance log.'
                ], 422);
            }

            // Confirm that the consultation motorcycle belongs to the consultation rider.
            $motorcycleBelongsToRider = $consultation->motorcycle()
                ->where('user_id', $consultation->user_id)
                ->exists();

            if (!$motorcycleBelongsToRider) {
                return response()->json([
                    'message' => 'The consultation motorcycle does not belong to the rider.'
                ], 422);
            }

            $log = MaintenanceLog::create([
                'consultation_id' => $consultation->id,
                'user_motorcycle_id' => $consultation->user_motorcycle_id,
                'mechanic_id' => $mechanic->id,
                'service_type' => $fields['service_type'],
                'description' => $fields['description'] ?? null,
                'odometer_reading' => $fields['odometer_reading'],
                'service_date' => now(),
                'cost' => $fields['cost'] ?? 0,
                'is_verified_by_mechanic' => true,
            ]);

            return response()->json([
                'message' => 'Maintenance log created successfully.',
                'log' => $log,
                'consultation' => $consultation->fresh(),
            ], 201);
        });
    }

    public function show(MaintenanceLog $maintenanceLog)
    {
        $this->authorize('view', $maintenanceLog);

        try {
            $maintenanceLog->load('motorcycle.brand', 'mechanic.user:id,name', 'diagnosticReport');
            return response()->json([
                'message' => 'Maintenance Log Retrieved Succesfully',
                'data' => $maintenanceLog
            ], 200);

        } catch (Exception $e) {
            Log::error("Maintenance Log Error [ID: {$maintenanceLog->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to Retrieve Maintenance Log',
                'error' => env('APP.DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}
