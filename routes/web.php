<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

// Rruga per migrate - PERDOR VETEM NJE HERE dhe fshije pas perdorimit!
Route::get('/run-migrations', function () {
    Artisan::call('migrate', ['--force' => true]);
    return 'Migrimet u ekzekutuan me sukses ✅';
});