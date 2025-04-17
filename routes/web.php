<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

// Rruga per migrate - PERDOR VETEM NJE HERE dhe fshije pas perdorimit!
Route::get('/run-migrations', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        Log::info("✅ Migrimet u ekzekutuan me sukses!");
        return 'Migrimet u ekzekutuan me sukses ✅';
    } catch (\Throwable $e) {
        Log::error("❌ Gabim gjatë migrimit: " . $e->getMessage());
        return response()->json([
            'error' => 'Gabim gjatë migrimit',
            'message' => $e->getMessage()
        ], 500);
    }
});
