<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\FaceAuthenticationController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\OfficerAccountabilityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public map view
Route::get('/map', [MapController::class, 'index'])->name('map.index');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'citizen.face'])->group(function () {

    Route::get('/face/enroll', [FaceAuthenticationController::class, 'enrollForm'])->name('face.enroll');
    Route::post('/face/enroll', [FaceAuthenticationController::class, 'enroll'])->name('face.enroll.store');

    // Dashboard (role-aware)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Reports CRUD
    Route::resource('reports', ReportController::class);

    // Report actions (officer/admin)
    Route::patch('/reports/{report}/status', [ReportController::class, 'updateStatus'])
        ->name('reports.update-status')
        ->middleware('role:officer,admin');

    Route::patch('/reports/{report}/assign', [ReportController::class, 'assign'])
        ->name('reports.assign')
        ->middleware('role:officer,admin');

    Route::prefix('admin/officers')->name('admin.officers.')->middleware('role:admin')->group(function () {
        Route::get('/{officer}/assignment-history', [OfficerAccountabilityController::class, 'history'])->name('history');
        Route::post('/{officer}/notify', [OfficerAccountabilityController::class, 'notify'])->name('notify');
        Route::post('/{officer}/escalate', [OfficerAccountabilityController::class, 'escalate'])->name('escalate');
    });

    // Comments
    Route::post('/reports/{report}/comments', [ReportController::class, 'comment'])
        ->name('reports.comment');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:admin,officer')->group(function () {
        Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
        Route::get('/verification/{user}', [VerificationController::class, 'show'])->name('verification.show');
        Route::get('/verification/{user}/id', [VerificationController::class, 'id'])->name('verification.id');
        Route::patch('/verification/{user}/approve', [VerificationController::class, 'approve'])->name('verification.approve');
        Route::patch('/verification/{user}/reject', [VerificationController::class, 'reject'])->name('verification.reject');
        Route::patch('/verification/{user}/revoke', [VerificationController::class, 'revoke'])->name('verification.revoke');
    });

    Route::post('/verification/upload', [VerificationController::class, 'upload'])
        ->middleware('role:citizen')
        ->name('verification.upload');

    Route::post('/admin/citizens/{user}/face-login/reset', [FaceAuthenticationController::class, 'resetEnrollment'])
        ->middleware('role:admin')
        ->name('admin.citizens.face.reset');
});

require __DIR__.'/auth.php';
