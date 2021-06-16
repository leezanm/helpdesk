<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::as('api.')->group(function () {
    Route::post('get-users-by-role', [App\Http\Controllers\Api\ListController::class, 'getUsersByRole'])->name('get-users-by-role');
    Route::post('get-user', [App\Http\Controllers\Api\UserController::class, 'show'])->name('get-user');
    Route::post('get-user-profile', [App\Http\Controllers\Api\UserController::class, 'getUserProfile'])->name('get-user-profile');

    Route::prefix('aduan')->as('aduan.')->group(function () {
        Route::post('/get-report/{report}', [App\Http\Controllers\Api\ReportController::class, 'show'])->name('get-report');
        Route::post('/update-feedback/{report}', [App\Http\Controllers\Api\ReportController::class, 'updateFeedback'])->name('update-feedback');
    });

    Route::prefix('laporan')->as('laporan.')->group(function () {
        Route::post('/by-status', [App\Http\Controllers\LaporanController::class, 'laporanByStatusApi'])->name('by-status');
        Route::post('/by-category', [App\Http\Controllers\LaporanController::class, 'laporanByCategoryApi'])->name('by-category');
        Route::post('/by-rating', [App\Http\Controllers\LaporanController::class, 'laporanByRatingApi'])->name('by-rating');
        Route::post('/by-role', [App\Http\Controllers\LaporanController::class, 'laporanByRoleyApi'])->name('by-role');
    });

    Route::prefix('dashboard')->as('dashboard.')->group(function () {
        Route::post('/pie-chart', [App\Http\Controllers\Api\DashboardController::class, 'pieChart'])->name('pie-chart');
        Route::post('/bar-chart', [App\Http\Controllers\Api\DashboardController::class, 'barChart'])->name('bar-chart');
        Route::post('/category-table', [App\Http\Controllers\Api\DashboardController::class, 'categoryTable'])->name('category-table');
    });
});


