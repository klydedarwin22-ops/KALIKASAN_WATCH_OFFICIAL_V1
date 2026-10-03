<?php

use App\Http\Controllers\DashboardController;
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

Route::middleware(['auth', 'verified'])->group(function () {

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
});

require __DIR__.'/auth.php';
