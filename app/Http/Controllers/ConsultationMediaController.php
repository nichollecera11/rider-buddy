<?php

namespace App\Http\Controllers;

use App\Models\ConsultationMedia;
use Exception;
use Illuminate\Support\Facades\Log;

class ConsultationMediaController extends Controller
{
    // Only destroy() is routed (apiResource(...)->only(['destroy'])), so the empty resource stubs were removed.
    // The parameter name must match the route: run `php artisan route:list --path=consultation-media`
    // and check it shows {consultation_medium}.
    public function destroy(ConsultationMedia $consultationMedium)
    {
        $this->authorize('delete', $consultationMedium);

        // Evidence is frozen once the consultation is completed, because a dispute may need it
        if ($consultationMedium->isLocked()) {
            return response()->json([
                'message' => 'Media cannot be removed once the consultation is underway'
            ], 409);
        }

        try {
            // Soft delete only. The file stays on disk, so the evidence can still be recovered.
            $consultationMedium->delete();

            return response()->json([
                'message' => 'Media removed successfully'
            ], 200);
        } catch (Exception $e) {
            Log::error("Consultation Media Delete Error [ID: {$consultationMedium->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to remove media',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}