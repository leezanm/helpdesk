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

    Route::prefix('notification')->as('notification.')->group(function () {

        Route::get('/', [App\Http\Controllers\NotificationController::class, 'listNotification']);
        Route::get('/read/{notification}', [App\Http\Controllers\NotificationController::class, 'read'])->name('read');
    });

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.index');

    Route::prefix('aduan')->as('aduan.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/user/{user:username}', [App\Http\Controllers\ReportController::class, 'indexUser'])->name('index-user');
        Route::get('/untuk-tindakan', [App\Http\Controllers\ReportController::class, 'indexUntukTindakan'])->name('index-untuk-tindakan');
        Route::get('/dihantar', [App\Http\Controllers\ReportController::class, 'indexDihantar'])->name('index-dihantar');
        Route::get('/create', [App\Http\Controllers\ReportController::class, 'create'])->name('create');
        Route::post('/create', [App\Http\Controllers\ReportController::class, 'store'])->name('store');
        Route::get('/edit/{report}', [App\Http\Controllers\ReportController::class, 'edit'])->name('edit');
        Route::post('/edit/{report}', [App\Http\Controllers\ReportController::class, 'update'])->name('update');
        Route::post('/update-task/{report}', [App\Http\Controllers\ReportController::class, 'assignTask'])->name('update-task');
        Route::get('/show/{report}', [App\Http\Controllers\ReportController::class, 'show'])->name('show')->middleware('update-aduan-status');
        Route::post('/feedback/{report}', [App\Http\Controllers\ReportController::class, 'updateFeedback'])->name('update-feedback');

        Route::post('/attachments/store', [App\Http\Controllers\ReportAttachmentController::class, 'store'])->name('attachments.store');
    });



    Route::get('/audit-trail', [App\Http\Controllers\AuditTrailController::class, 'index'])->middleware('can:manage.audit-trail');
    Route::get('/selenggara', [App\Http\Controllers\MaintenanceController::class, 'index']);

    // Route::prefix('department')->as('department.')->group(function () {
    //     Route::get('/', [App\Http\Controllers\DepartmentController::class, 'index'])->name('index');
    //     Route::post('/create', [App\Http\Controllers\DepartmentController::class, 'store'])->name('store');
    //     Route::get('/show/{department}', [App\Http\Controllers\DepartmentController::class, 'show'])->name('show');
    //     Route::get('/edit/{department}', [App\Http\Controllers\DepartmentController::class, 'edit'])->name('edit');
    //     Route::post('/update/{department}', [App\Http\Controllers\DepartmentController::class, 'update'])->name('update');
    //     Route::post('/destroy/{department}', [App\Http\Controllers\DepartmentController::class, 'destroy'])->name('destroy');
    // });

    Route::prefix('kategori')->as('category.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportCategoryController::class, 'index'])->name('index');
        Route::post('/create', [App\Http\Controllers\ReportCategoryController::class, 'store'])->name('store');
        Route::get('/show/{reportCategory}', [App\Http\Controllers\ReportCategoryController::class, 'show'])->name('show');
        Route::get('/edit/{reportCategory}', [App\Http\Controllers\ReportCategoryController::class, 'edit'])->name('edit');
        Route::post('/update/{reportCategory}', [App\Http\Controllers\ReportCategoryController::class, 'update'])->name('update');
        Route::post('/destroy/{reportCategory}', [App\Http\Controllers\ReportCategoryController::class, 'destroy'])->name('destroy');
    });

    // Route::view('/pengguna', 'superadmin.pengguna');

    Route::prefix('pengguna')->as('user.')->group(function(){
        Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('index');
        Route::post('/create', [App\Http\Controllers\UserController::class, 'storeUser'])->name('store');
        Route::get('/show/{user}', [App\Http\Controllers\UserController::class, 'showUser'])->name('showUser');
        Route::get('/edit/{user}', [App\Http\Controllers\UserController::class, 'editUser'])->name('editUser');
        Route::post('/update/{user}', [App\Http\Controllers\UserController::class, 'updateUser'])->name('updateUser');
        Route::post('/delete/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
        Route::get('/restore/{user}', [App\Http\Controllers\UserController::class, 'restore'])->name('restore');
        Route::get('/peg-teknikal', [App\Http\Controllers\UserController::class, 'indexPegTeknikal'])->name('index');
        Route::get('/pentadbir', [App\Http\Controllers\UserController::class, 'indexPentadbir'])->name('index');
    });


    Route::prefix('laporan')->as('laporan.')->group(function () {
        Route::get('/', [App\Http\Controllers\LaporanController::class,'index']);
        Route::get('/mengikut-status-tindakan', [App\Http\Controllers\LaporanController::class, 'viewByStatus'])->name('view-by-status');
        Route::get('/mengikut-kategori', [App\Http\Controllers\LaporanController::class, 'viewByCategory'])->name('view-by-category');
        Route::get('/soal-selidik', [App\Http\Controllers\LaporanController::class, 'viewByRating'])->name('view-by-rating');
        Route::get('/mengikut-user', [App\Http\Controllers\LaporanController::class, 'viewByUser'])->name('view-by-user');
        Route::get('/mengikut-peranan', [App\Http\Controllers\LaporanController::class, 'viewByRole'])->name('view-by-role');

    });

    Route::prefix('peranan')->as('role.')->group(function () {
        Route::get('/', [App\Http\Controllers\RoleManagementController::class, 'index'])->name('index');
        Route::get('/show/{role:name}', [App\Http\Controllers\RoleManagementController::class, 'show'])->name('show');
        Route::post('/create', [App\Http\Controllers\RoleManagementController::class, 'store']);
        Route::post('/edit/{role:name}', [App\Http\Controllers\RoleManagementController::class, 'update']);
    });

    Route::view('/pengesahan', 'superadmin.selenggara');
    Route::view('/sijil', 'superadmin.selenggara');
    Route::view('/pengumuman', 'pengumuman');
    // Route::view('/backup', 'superadmin.backup');

    Route::prefix('account')->group(function () {
        Route::get('profile/{user}', [App\Http\Controllers\UserController::class, 'edit'])->name('account.profile.show');
        Route::post('profile/edit/{user}', [App\Http\Controllers\UserController::class, 'update'])->name('account.profile.update');
        Route::post('password/edit/{user}', [App\Http\Controllers\UserController::class, 'passwordUpdate'])->name('account.password.update');
    });

    Route::prefix('backup')->as('backup.')->middleware('can:manage.backup')->group(function () {
        Route::get('/', [App\Http\Controllers\BackupContoller::class, 'index'])->name('index');
        Route::get('/full', [App\Http\Controllers\BackupContoller::class, 'backupFull'])->name('full');
        Route::get('/database', [App\Http\Controllers\BackupContoller::class, 'backupDB'])->name('database');
        Route::get('/file', [App\Http\Controllers\BackupContoller::class, 'backupFile'])->name('file');
        Route::get('/download/{filename}', [App\Http\Controllers\BackupContoller::class, 'download'])->name('download');
        Route::get('/delete/{filename}', [App\Http\Controllers\BackupContoller::class, 'destroy'])->name('delete');
    });
});
