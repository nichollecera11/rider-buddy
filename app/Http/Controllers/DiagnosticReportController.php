<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiagnosticReportRequest;
use App\Http\Requests\UpdateDiagnosticReportRequest;
use App\Models\Consultation;
use App\Models\DiagnosticReport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnosticReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        try {
            $query = DiagnosticReport::with([
                'consultation.motorcycle',
                'mechanic.user',
                'maintenanceLogs', //Optional, if you want to show if it was repaired
            ]);

            // 2. Role Filtering (Crucial for Privacy)
            if ($user->mechanic) {
                $query->where('mechanic_id', $user->mechanic->id);
            } else {
                $query->whereHas('consultation', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $query->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })

                ->when($request->severity, function ($q, $severity) {
                    $q->where('severity', $severity);
                })

                ->when($request->search, function ($q, $search) {
                    $q->where(function ($innerQuery) use ($search) {
                        $innerQuery->where('findings', 'like', "%$search%")
                            ->orWhere('recommended_repairs', 'like', "%$search%")
                            ->orWhereHas('consultation.motorcycle', function ($motorcycleQuery) use ($search) {
                                // Let them search by the bike model or plate number too!
        
                                $motorcycleQuery->where('model', 'like', "%{$search}%")
                                    ->orWhere('plate_number', 'like', "%{$search}%");
                            });
                    });
                });

            $reports = $query->latest()->paginate(10);

            return response()->json([
                'message' => 'Diagnostic Reports Retrieved Successfully',
                'data' => $reports,
            ], 200);
        } catch (Exception $e) {
            Log::error('Diagnostic Report Index Error' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to Retrieve Diagnostic Reports',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Store a newly created diagnostic report.
     */
    public function store(StoreDiagnosticReportRequest $request, Consultation $consultation)
    {
        if (!$consultation->isAssignedMechanic(auth()->user())) {
            return response()->json([
                'message' => 'Unauthorized, Only the assigned Mechanic can issue this report'
            ], 403);
        }
        if ($consultation->diagnosticReport()->exists()) {
            return response()->json([
                'message' => 'A diagnostic report already exists for this consultation.'
            ], 422);
        }
        DB::beginTransaction();
        try {

            $report = $consultation->diagnosticReport()->create([
                'mechanic_id' => $consultation->mechanic_id,
                ...$request->validated(),
                'issued_at' => $request->status === 'issued' ? now() : null,

            ]);
            DB::commit();
            return response()->json([
                'message' => 'Diagnostic report created successfully',
                'data' => $report->load(['consultation', 'mechanic.user'])
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Create Diagnostic Report Error: " . $e->getMessage());

            return response()->json([
                'message' => 'Error creating diagnostic report',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DiagnosticReport $diagnostic_report)
    {
        $this->authorize('view', $diagnostic_report);

        try {
            $diagnostic_report->load(['consultation.userMotorcycle', 'mechanic.user', 'maintenanceLogs']);
            return response()->json([
                'message' => 'Diagnostic Report Details Retrieved',
                'data' => $diagnostic_report
            ], 200);
        } catch (Exception $e) {
            Log::error("Diagnostic Report Show Error [ID: {$diagnostic_report->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve Diagnostic Report',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDiagnosticReportRequest $request, DiagnosticReport $diagnostic_report)
    {
        $this->authorize('update', $diagnostic_report);

        DB::beginTransaction();
        try {
            $fields = $request->validated();

            if (isset($fields['status']) && $fields['status'] === 'issued' && !$diagnostic_report->issued_at) {
                $fields['issued_at'] = now();
            }

            $diagnostic_report->update($fields);

            DB::commit();

            return response()->json([
                'message' => 'Diagnostic Report Updated Successfully',
                'data' => $diagnostic_report
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Diagnostic Report Update Error: " . $e->getMessage());

            return response()->json([
                'message' => 'Failed to Update Diagnostic Report',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiagnosticReport $diagnostic_report)
    {
        $this->authorize('delete', $diagnostic_report);

        DB::beginTransaction();

        try {
            $diagnostic_report->delete(); // This will soft delete based on the schema you provided earlier

            DB::commit();
            return response()->json(['message' => 'Diagnostic Report Successfully Deleted'], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Delete Diagnostic Report Error: " . $e->getMessage());

            return response()->json([
                'message' => 'Delete Diagnostic Report Failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}