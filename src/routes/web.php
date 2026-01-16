<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use Illuminate\Http\Request;

Route::middleware(['auth', 'verified', 'user.role'])->group(function () {

    Route::get('/attendance', [AttendanceController::class, 'attendance'])
        ->name('attendance');

    Route::get('/attendance/list', [AttendanceController::class, 'attendanceList'])
        ->name('attendance.list');

    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'attendanceDetail'])
        ->name('attendance.detail');

    Route::post('/work/start', [AttendanceController::class, 'start'])
        ->name('work.start');

    Route::post('/work/end', [AttendanceController::class, 'end'])
        ->name('work.end');

    Route::post('/rest/start', [AttendanceController::class, 'rest'])
        ->name('rest.start');

    Route::post('/rest/end', [AttendanceController::class, 'restEnd'])
        ->name('rest.end');
});

Route::post('/attendance/detail/{work}/request', [AttendanceController::class, 'storeCorrectionRequest'])
    ->middleware(['auth', 'verified'])
    ->name('attendance.request.store');

Route::get('/stamp_correction_request/list', [AttendanceController::class, 'userStampCorrectionList'])
    ->middleware(['auth', 'verified'])
    ->name('stamp.correction.user.list');

Route::get('/admin/staff/list', [AttendanceController::class, 'staffList'])
    ->name('admin.staff.list')
    ->middleware(['auth', 'can:admin']);

Route::get('/admin/attendance/staff/{id}', [AttendanceController::class, 'adminAttendanceStaff'])
    ->name('admin.attendance.staff')
    ->middleware(['auth', 'can:admin']);

Route::get(
    '/admin/attendance/staff/{id}/csv',
    [AttendanceController::class, 'adminAttendanceStaffCsv']
)->name('admin.attendance.staff.csv')
    ->middleware(['auth', 'can:admin']);

Route::get('/admin/login', fn() => view('admin_login'))
    ->name('admin.login');

Route::prefix('admin')
    ->middleware(['auth', 'can:admin'])
    ->group(function () {
        Route::get('/attendance/list', [AttendanceController::class, 'adminAttendanceList'])
            ->name('admin.attendance.list');
    });

Route::prefix('admin')
    ->middleware(['auth', 'can:admin'])
    ->group(function () {
        Route::get('/attendance/detail/{id}', [AttendanceController::class, 'adminAttendanceDetail'])
            ->name('admin.attendance.detail');
    });

Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{id}', [AttendanceController::class, 'showApprovePage'])
        ->name('admin.request.approve.show');

    Route::post('/request/approve/{id}', [AttendanceController::class, 'approveCorrectionRequest'])
        ->name('admin.request.approve');
});

Route::get('/verify-info', function () {
    return view('auth.verify-email');
})->name('verify.info');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
