<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\ServicesController;
use App\Http\Controllers\Api\TimingsController;
use App\Http\Controllers\Api\FormsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('download/clients', [UsersController::class, 'download']);
    Route::get('download/services', [ServicesController::class, 'download']);
    Route::get('download/cases', [ServicesController::class, 'downloadCases'])->name('downloadCases');
    Route::get('download/mdr', [ServicesController::class, 'downloadMDR'])->name('downloadMDR');
    Route::get('download/timing-documents', [TimingsController::class, 'downloadDocuments'])->name('downloadDocuments');
    Route::get('download/form-document/{project}/{user}/{form}/{document}', [FormsController::class, 'downloadDocument']);
    Route::get('download/training', [UsersController::class, 'downloadTraining'])->name('downloadTraining');
    Route::get('download/rides', [TimingsController::class, 'downloadRides']);
    Route::get('download/factors', [UsersController::class, 'downloadFactors']);
});

Route::get('{path}', function () {
    return view('app');
})->where('path', '((?!api).)*');
