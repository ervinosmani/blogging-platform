<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-migrations', function () {
    try {
        // Kontrollo nese tabela sessions ekziston
        if (!\Schema::hasTable('sessions')) {
            Artisan::call('migrate', ['--force' => true]);
            return 'Migrimet u aplikuan me sukses';
        }

        return 'Migrimet jane te aplikuara tashme';
    } catch (\Exception $e) {
        Log::error("Gabim me migrimet: " . $e->getMessage());
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

