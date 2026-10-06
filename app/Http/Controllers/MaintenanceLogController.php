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
            $query = MaintenanceLog::with(['motorcycle.brand', 'mechanic.user']);

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
    public function store(StoreMaintenanceLogRequest $request)
    {

        $this->authorize('create', MaintenanceLog::class);

        $fields = $request->validated();

        $consultation = Consultation::findOrFail($fields['consultation_id']);
        $mechanic = auth()->user()->mechanic;

        if (!$mechanic || $consultation->mechanic_id !== $mechanic->id) {
            return response()->json([
                'message' => 'Unauthorized. Please contact your administrator'
            ], 403);
        }

        if ($consultation->status !== 'ongoing') {
            return response()->json([
                'message' => 'Service must be ongoing before you can proceed'
            ], 422);
        }
        DB::beginTransaction();

        try {
            $log = MaintenanceLog::create([
                'user_motorcycle_id' => $consultation->user_motorcycle_id,
                'mechanic_id' => $mechanic->id,
                'service_type' => $fields['service_type'],
                'description' => $fields['description'],
                'odometer_reading' => $fields['odometer_reading'],
                'service_date' => now(),
                'cost' => $fields['cost'],
                'is_verified_by_mechanic' => true

            ]);

            $consultation->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'estimated_repair_costs' => $fields['cost']
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Service Completed and Logged Successfully',
                'log' => $log,
                'consultation' => $consultation
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Maintenace Log Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to Complete Service, Make sure you Logged Successfully.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MaintenanceLog $maintenanceLog)
    {
        $this->authorize('view', $maintenanceLog);

        try {
            $maintenanceLog->load('motorcycle.brand', 'mechanic.user', 'diagnosticReport');
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaintenanceLog $maintenanceLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaintenanceLog $maintenanceLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $log = MaintenanceLog::find($id);

        if (!$log) {
            return response()->json([
                'message' => 'Maintenance Log not Found'
            ], 404);

        }
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized, Only Administrator Can Delete Maintenance Logs'
            ], 403);

        }

        DB::beginTransaction();
        try {
            $log->delete();
            DB::commit();
            return response()->json([
                'message' => 'Service Record Sucessfully Deleted'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Maintenance Log Delete Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Delete Failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }

    }
}
