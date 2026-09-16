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
                                    ->orWhere('plate_number', 'like', "%{$search}");
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
        $user = auth()->user();
        //Find the Report and eager load necessary relationships
        try {
            $report = DiagnosticReport::with(['consultation.userMotorcycle', 'mechanic.user', 'maintenanceLogs'])->find($id);

            if (!$report) {
                return response()->json(['message' => 'Diagnostic report not found'], 404);
            }
            $isOwner = $report->consultation->user_id === $user->id;

            // Is the current user the assigned mechanic?
            $isAssignedMechanic = $user->mechanic && $report->mechanic_id === $user->mechanic->id;
            if (!$isOwner && !$isAssignedMechanic) {
                return response()->json([
                    'message' => 'Unauthorized. You do not have access to this Diagnostic Report.'
                ], 403);
            }
            return response()->json([
                'message' => 'Diagnostic Report Details Retrieved',
                'data' => $report
            ], 200);
        } catch (Exception $e) {
            Log::error("Diagnostic Report Show Error [ID: {$id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve Diagnostic Report',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
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