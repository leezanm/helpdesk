<?php

use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\OrganizationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});
Route::view('/forgot-password-out', 'auth.forgot_password_out');

Route::middleware(['auth', 'audit-trail'])->group(function () {
    Route::view('/test', 'example');

    Route::view('/dashboard', 'dashboard');

    Route::view('/fbi', 'fbi.index');
    Route::view('/fbi-create', 'fbi.create');

    Route::prefix('fbi')->group(function () {
        Route::get('/', [App\Http\Controllers\FbiController::class, 'index'])->name('fbi.index');
        Route::get('/show/{fbi}', [App\Http\Controllers\FbiController::class, 'show'])->name('fbi.show');
        Route::post('/create', [App\Http\Controllers\FbiController::class, 'store'])->name('fbi.store');
        Route::post('/edit/{fbi}', [App\Http\Controllers\FbiController::class, 'edit'])->name('fbi.edit');
        Route::post('/update/{fbi}', [App\Http\Controllers\FbiController::class, 'update'])->name('fbi.update');
        Route::post('/delete/{fbi}', [App\Http\Controllers\FbiController::class, 'destroy'])->name('fbi.destroy');
    });

    Route::prefix('mbi')->group(function () {
        Route::get('/', [App\Http\Controllers\MbiController::class, 'index'])->name('mbi.index');
        Route::get('/show/{mbi}', [App\Http\Controllers\MbiController::class, 'show'])->name('mbi.show');
        Route::post('/create', [App\Http\Controllers\MbiController::class, 'store'])->name('mbi.store');
        Route::post('/edit/{mbi}', [App\Http\Controllers\MbiController::class, 'edit'])->name('mbi.edit');
        Route::post('/update/{mbi}', [App\Http\Controllers\MbiController::class, 'update'])->name('mbi.update');
        Route::post('/delete/{mbi}', [App\Http\Controllers\MbiController::class, 'destroy'])->name('mbi.destroy');
    });

    Route::get('/audit-trail', [App\Http\Controllers\AuditTrailController::class, 'index']);
    Route::get('/selenggara', [App\Http\Controllers\MaintenanceController::class, 'index']);

    Route::prefix('division')->as('division.')->group(function () {
        Route::get('/', [App\Http\Controllers\DivisionController::class, 'index'])->name('index');
        Route::post('/create', [App\Http\Controllers\DivisionController::class, 'store'])->name('store');
        Route::get('/show/{division}', [App\Http\Controllers\DivisionController::class, 'show'])->name('show');
        Route::get('/edit/{division}', [App\Http\Controllers\DivisionController::class, 'edit'])->name('edit');
        Route::post('/update/{division}', [App\Http\Controllers\DivisionController::class, 'update'])->name('update');
        Route::post('/destroy/{division}', [App\Http\Controllers\DivisionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('type')->as('type.')->group(function () {
        Route::get('/', [App\Http\Controllers\TypeController::class, 'index'])->name('index');
        Route::post('/create', [App\Http\Controllers\TypeController::class, 'store'])->name('store');
        Route::get('/show/{type}', [App\Http\Controllers\TypeController::class, 'show'])->name('show');
        Route::get('/edit/{type}', [App\Http\Controllers\TypeController::class, 'edit'])->name('edit');
        Route::post('/update/{type}', [App\Http\Controllers\TypeController::class, 'update'])->name('update');
        Route::post('/destroy/{type}', [App\Http\Controllers\TypeController::class, 'destroy'])->name('destroy');
    });

    Route::view('/pengguna', 'superadmin.pengguna');
    Route::view('/pegawai', 'superadmin.pentadbir');

    Route::view('/pengesahan', 'superadmin.selenggara');
    Route::view('/sijil', 'superadmin.selenggara');
    Route::view('/pengumuman', 'pengumuman');
    Route::view('/peranan', 'peranan');
    Route::view('/backup', 'superadmin.backup');

    Route::prefix('account')->group(function () {
        Route::get('profile/{user}', [App\Http\Controllers\UserController::class, 'edit'])->name('account.profile.show');
        Route::get('profile/edit/{user}', [App\Http\Controllers\UserController::class, 'edit'])->name('account.profile.edit');
        Route::post('profile/edit/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('account.profile.update');
        Route::get('password/edit/{user}', [App\Http\Controllers\UserController::class, 'passwordEdit'])->name('account.password.edit');
        Route::post('password/edit/{user}', [App\Http\Controllers\UserController::class, 'passwordUpdate'])->name('account.password.update');
    });
});
