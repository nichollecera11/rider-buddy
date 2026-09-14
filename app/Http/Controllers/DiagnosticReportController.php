<?php

namespace App\Http\Controllers;

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
        // Example index: gets all reports. You can filter this later by user or mechanic.
        $reports = DiagnosticReport::with(['consultation', 'mechanic.user'])->latest()->paginate(10);
        return response()->json($reports);
    }

    /**
     * Store a newly created diagnostic report.
     */
    public function store(Request $request, $consultationId)
    {
        $fields = $request->validate([
            'findings' => 'required|string',
            'recommended_repairs' => 'nullable|string',
            'severity' => 'required|in:minor,moderate,urgent',
            'status' => 'required|in:draft,issued', 
        ]);

        DB::beginTransaction();

        try {
            $consultation = Consultation::with('mechanic')->findOrFail($consultationId);

            // Security Check: Only the assigned mechanic can write the report.
            // Note: consultation->mechanic_id is the mechanic profile ID, so we check the user_id of that profile.
            if ($consultation->mechanic->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized. Only the assigned mechanic can issue this report.'
                ], 403);
            }

            // Prevent duplicate reports for a single consultation
            if ($consultation->diagnosticReport()->exists()) {
                return response()->json([
                    'message' => 'A diagnostic report already exists for this consultation.'
                ], 422);
            }

            $report = $consultation->diagnosticReport()->create([
                'mechanic_id' => $consultation->mechanic_id,
                'findings' => $fields['findings'],
                'recommended_repairs' => $fields['recommended_repairs'] ?? null,
                'severity' => $fields['severity'],
                'status' => $fields['status'],
                'issued_at' => $fields['status'] === 'issued' ? now() : null,
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
    public function show(string $id)
    {
        $report = DiagnosticReport::with(['consultation.userMotorcycle', 'mechanic.user', 'maintenanceLogs'])->find($id);
        
        if (!$report) {
            return response()->json(['message' => 'Diagnostic report not found'], 404);
        }
        
        return response()->json($report);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $report = DiagnosticReport::with('mechanic')->find($id);

        if (!$report) {
            return response()->json(['message' => 'Diagnostic report not found'], 404);
        }

        // Security Check for user update only
        if ($report->mechanic->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized user profile'
            ], 403);
        }

        $fields = $request->validate([
            'findings' => 'sometimes|required|string',
            'recommended_repairs' => 'nullable|string',
            'severity' => 'sometimes|required|in:minor,moderate,urgent',
            'status' => 'sometimes|required|in:draft,issued',
        ]);

        DB::beginTransaction();

        try {
            // Automatically stamp 'issued_at' if the status is being flipped from draft to issued
            if (isset($fields['status']) && $fields['status'] === 'issued' && !$report->issued_at) {
                $fields['issued_at'] = now();
            }

            $report->update($fields);

            DB::commit();

            return response()->json([
                'message' => 'Diagnostic Report Updated Successfully',
                'data' => $report
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
    public function destroy(string $id)
    {
        $report = DiagnosticReport::with('mechanic')->find($id);

        if (!$report) {
            return response()->json(['message' => 'Diagnostic report not found'], 404);
        }

        if ($report->mechanic->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {
            $report->delete(); // This will soft delete based on the schema you provided earlier
            
            DB::commit();
            return response()->json(['message' => 'Diagnostic Report Successfully Deleted'], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Delete Diagnostic Report Error: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Delete Diagnostic Report Failed', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}