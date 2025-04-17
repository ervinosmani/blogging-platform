<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Route::get('/run-migrations', function () {
    try {
        Log::info("Po ekzekutohet sessions migration...");
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2025_04_17_144732_create_sessions_table.php',
            '--force' => true
        ]);
        return 'Vetëm sessions migration u aplikua me sukses ✅';
    } catch (\Exception $e) {
        Log::error("Gabim në sessions migration: " . $e->getMessage());
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

