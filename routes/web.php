<?php

use Illuminate\Support\Facades\Route;

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

// Public, read-only aduan preview reachable from the email link WITHOUT login.
// Protected by a signed URL (HMAC) so the link cannot be tampered/guessed, and
// it expires. It shows the aduan details only — taking action still needs login.
Route::get('/aduan/pratonton/{report}', [App\Http\Controllers\ReportController::class, 'publicView'])
    ->name('aduan.public-view')
    ->middleware('signed');

Route::middleware(['auth', 'audit-trail', 'ensure-phone'])->group(function () {

    Route::prefix('notification')->as('notification.')->group(function () {

        Route::get('/', [App\Http\Controllers\NotificationController::class, 'listNotification']);
        Route::get('/read/{notification}', [App\Http\Controllers\NotificationController::class, 'read'])->name('read');
    });

    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.index');
    // Org-wide dashboard — management roles only (see access-main-dashboard gate).
    Route::get('/dashboard/utama', [\App\Http\Controllers\DashboardController::class, 'main'])
        ->name('dashboard.main')
        ->middleware('can:access-main-dashboard');

    Route::prefix('aduan')->as('aduan.')->group(function () {
        // Interactive CRUD/flow handled by Livewire (App\Livewire\Aduan\*).
        // Controllers render page wrappers that mount the components.
        Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('index')->middleware('can:aduan.all');
        Route::get('/user/{user:username}', [App\Http\Controllers\ReportController::class, 'indexUser'])->name('index-user');
        Route::get('/untuk-tindakan', [App\Http\Controllers\ReportController::class, 'indexUntukTindakan'])->name('index-untuk-tindakan')->middleware('can:aduan.updateTask');
        Route::get('/dihantar', [App\Http\Controllers\ReportController::class, 'indexDihantar'])->name('index-dihantar')->middleware('can:aduan.adminTask');
        Route::get('/create', [App\Http\Controllers\ReportController::class, 'create'])->name('create');
        Route::get('/edit/{report}', [App\Http\Controllers\ReportController::class, 'edit'])->name('edit');
        Route::get('/show/{report}', [App\Http\Controllers\ReportController::class, 'show'])->name('show');
        Route::get('/lesson-learn', [App\Http\Controllers\ReportController::class, 'lessonLearn'])->name('lesson-learn')->middleware('can:aduan.updateTask');
    });



    Route::get('/audit-trail', [App\Http\Controllers\AuditTrailController::class, 'index'])->middleware('can:manage.audit-trail');
    Route::get('/selenggara', [App\Http\Controllers\MaintenanceController::class, 'index']);

    // Notification recipient settings — superadmin only (manage.role permission).
    Route::view('/tetapan/notifikasi', 'settings.notifications')
        ->name('settings.notifications')
        ->middleware('can:manage.role');

    Route::prefix('kategori')->as('category.')->middleware('can:manage.category')->group(function () {
        // CRUD now handled by Livewire (App\Livewire\Category\*); only the
        // page wrapper route remains.
        Route::get('/', [App\Http\Controllers\ReportCategoryController::class, 'index'])->name('index');
    });

    Route::prefix('status')->as('status.')->middleware('can:manage.category')->group(function () {
        // Edit now handled by Livewire (App\Livewire\Status\StatusIndex);
        // only the page wrapper route remains.
        Route::get('/', [App\Http\Controllers\ReportStatusController::class, 'index'])->name('index');
    });

    // Route::view('/pengguna', 'superadmin.pengguna');
    Route::view('/pengguna-show', 'user.show');

    // User management is admin-only (matches the navbar's manage.user/manage.admin
    // gate). This also closes IDOR on /pengguna/show/full/{user}: a normal user
    // can no longer view another user's full profile by altering the id.
    Route::prefix('pengguna')->as('user.')->middleware('can:manage.user')->group(function(){
        // List + create/edit/deactivate handled by Livewire (App\Livewire\User\*).
        Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('index');
        Route::get('/show/full/{user}', [App\Http\Controllers\UserController::class, 'showFullUser'])->name('showFullUser');
        Route::get('/peg-teknikal', [App\Http\Controllers\UserController::class, 'indexPegTeknikal'])->name('indexPegTeknikal');
        Route::get('/pengurusan', [App\Http\Controllers\UserController::class, 'indexPengurusan'])->name('indexPengurusan');
        Route::get('/pentadbir', [App\Http\Controllers\UserController::class, 'indexPentadbir'])->name('indexPentadbir');
        // Asset management handled by Livewire (App\Livewire\User\UserAssetManager).
    });


    // Laporan exposes org-wide report data — management roles only, same gate
    // as the org-wide dashboard.
    Route::prefix('laporan')->as('laporan.')->middleware('can:access-main-dashboard')->group(function () {
        Route::get('/', [App\Http\Controllers\LaporanController::class,'index']);
        Route::get('/mengikut-status-tindakan', [App\Http\Controllers\LaporanController::class, 'viewByStatus'])->name('view-by-status');
        Route::get('/mengikut-kategori', [App\Http\Controllers\LaporanController::class, 'viewByCategory'])->name('view-by-category');
        Route::get('/soal-selidik', [App\Http\Controllers\LaporanController::class, 'viewByRating'])->name('view-by-rating');
        // Route::get('/mengikut-user', [App\Http\Controllers\LaporanController::class, 'viewByUser'])->name('view-by-user');
        Route::get('/mengikut-bahagian', [App\Http\Controllers\LaporanController::class, 'viewByDepartment'])->name('view-by-department');
        Route::get('/mengikut-peranan', [App\Http\Controllers\LaporanController::class, 'viewByRole'])->name('view-by-role');
        Route::get('/keseluruhan', [App\Http\Controllers\LaporanController::class, 'viewByAll'])->name('view-by-all');

    });

    Route::prefix('peranan')->as('role.')->group(function () {
        // Listing handled by Livewire (App\Livewire\Role\RoleIndex).
        // The store/update endpoints were non-functional stubs and are removed.
        Route::get('/', [App\Http\Controllers\RoleManagementController::class, 'index'])->name('index');
        Route::get('/show/{role:name}', [App\Http\Controllers\RoleManagementController::class, 'show'])->name('show');
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
        Route::get('/', [App\Http\Controllers\BackupController::class, 'index'])->name('index');
        Route::get('/full', [App\Http\Controllers\BackupController::class, 'backupFull'])->name('full');
        Route::get('/database', [App\Http\Controllers\BackupController::class, 'backupDB'])->name('database');
        Route::get('/file', [App\Http\Controllers\BackupController::class, 'backupFile'])->name('file');
        Route::get('/download/{filename}', [App\Http\Controllers\BackupController::class, 'download'])->name('download');
        Route::get('/delete/{filename}', [App\Http\Controllers\BackupController::class, 'destroy'])->name('delete');
    });


});
