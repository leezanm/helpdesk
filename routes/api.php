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

// These endpoints are called by the logged-in web frontend (AJAX with the
// session cookie), and several expose user/report/org-wide data or MUTATE a
// report (update-feedback). Require an authenticated session (web guard) —
// previously this whole group was PUBLIC.
Route::middleware('auth')->as('api.')->group(function () {
    Route::post('get-users-by-role', [App\Http\Controllers\Api\ListController::class, 'getUsersByRole'])->name('get-users-by-role');

    // Username lookup over the local users table AND Active Directory. It is
    // the "bagi pihak" (wakil) lookup, so it is limited to the staff allowed to
    // represent others across bahagian — IT support and helpdesk. A warga
    // biasa must not be able to fish for other staff's name/email/bahagian.
    // (The Livewire aduan form does its own lookup; this endpoint is legacy.)
    Route::post('get-user', [App\Http\Controllers\Api\UserController::class, 'show'])
        ->middleware('can:aduan.representAny')
        ->name('get-user');
    Route::post('get-user-profile', [App\Http\Controllers\Api\UserController::class, 'getUserProfile'])->name('get-user-profile');

    Route::prefix('aduan')->as('aduan.')->group(function () {
        Route::post('/get-report/{report}', [App\Http\Controllers\Api\ReportController::class, 'show'])->name('get-report');
        Route::post('/update-feedback/{report}', [App\Http\Controllers\Api\ReportController::class, 'updateFeedback'])->name('update-feedback');
    });

    Route::prefix('laporan')->as('laporan.')->group(function () {
        Route::post('/by-status', [App\Http\Controllers\LaporanController::class, 'laporanByStatusApi'])->name('by-status');
        Route::post('/by-category', [App\Http\Controllers\LaporanController::class, 'laporanByCategoryApi'])->name('by-category');
        Route::post('/by-risk', [App\Http\Controllers\LaporanController::class, 'laporanByRiskApi'])->name('by-risk');
        Route::post('/by-rating', [App\Http\Controllers\LaporanController::class, 'laporanByRatingApi'])->name('by-rating');
        Route::post('/by-role', [App\Http\Controllers\LaporanController::class, 'laporanByRoleyApi'])->name('by-role');
        Route::post('/by-user', [App\Http\Controllers\LaporanController::class, 'laporanByUserApi'])->name('by-user');
        Route::post('/by-department', [App\Http\Controllers\LaporanController::class, 'laporanByDepartmentApi'])->name('by-department');
    });

    Route::prefix('dashboard')->as('dashboard.')->group(function () {
        Route::post('/pie-chart', [App\Http\Controllers\Api\DashboardController::class, 'pieChart'])->name('pie-chart');
        Route::post('/bar-chart', [App\Http\Controllers\Api\DashboardController::class, 'barChart'])->name('bar-chart');
        Route::post('/category-table', [App\Http\Controllers\Api\DashboardController::class, 'categoryTable'])->name('category-table');
    });
});


