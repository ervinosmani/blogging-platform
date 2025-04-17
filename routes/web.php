<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Route::get('/run-migrations', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);

        return 'Të gjitha migrimet u aplikuan me sukses ✅';
    } catch (\Exception $e) {
        Log::error("Gabim me migrimet: " . $e->getMessage());
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
