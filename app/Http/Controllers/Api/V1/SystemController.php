<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    /**
     * Clear system cache (Strictly Super Admin only)
     */
    public function clearCache(Request $request)
    {
        try {
            // Failsafe: Ensure only super_admin can trigger this
            if ($request->user() && !$request->user()->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Super Admin access required.'
                ], 403);
            }

            // Run artisan optimize:clear safely
            Artisan::call('optimize:clear');
            
            return response()->json([
                'success' => true,
                'message' => 'System cache, views, and routes cleared successfully.',
                'details' => Artisan::output()
            ], 200);

        } catch (\Exception $e) {
            Log::error("Cache clear failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}