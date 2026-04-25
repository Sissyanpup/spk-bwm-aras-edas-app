<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SPKController;

// Hapus Route::get('/'...) yang duplikat dan arahkan langsung ke Controller
Route::get('/', [SPKController::class, 'index'])->name('spk.index');

Route::get('/test', function () {
    return view('helloworld');
});

// Grouping Rute SPK untuk pengelolaan yang lebih baik
Route::prefix('spk')->name('spk.')->group(function () {
    Route::post('/preview', [SPKController::class, 'preview'])->name('preview');
    Route::post('/weighting', [SPKController::class, 'weighting'])->name('weighting');
    Route::post('/calculate', [SPKController::class, 'calculate'])->name('calculate');
    Route::post('/export', [SPKController::class, 'export'])->name('export');
    Route::get('/calculation', [SPKController::class, 'calculation'])->name('calculation');
    Route::get('/results', [SPKController::class, 'results'])->name('results');
});