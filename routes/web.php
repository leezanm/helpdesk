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

Route::middleware(['auth'])->group(function () {
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

    Route::view('/audit-trail', 'superadmin.audit_trail');
    Route::view('/pengguna', 'superadmin.pengguna');
    Route::view('/pegawai', 'superadmin.pentadbir');
    Route::view('/selenggara', 'superadmin.selenggara');
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
