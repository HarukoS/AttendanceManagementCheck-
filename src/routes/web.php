<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

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

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'attendance'])
        ->name('attendance');
});
Route::get('/attendance/list', [AttendanceController::class, 'attendanceList'])
    ->name('attendance.list');

Route::get('/attendance/detail/{id}', [AttendanceController::class, 'attendanceDetail'])->name('attendance.detail');

Route::post(
    '/attendance/detail/{work}/request',
    [AttendanceController::class, 'storeCorrectionRequest']
)->name('attendance.request.store');

Route::get('/stamp_correction_request/list', [AttendanceController::class, 'userStampCorrectionList'])
    ->middleware('auth')
    ->name('stamp.correction.user.list');

// スタッフ一覧
Route::get('/admin/staff/list', [AttendanceController::class, 'staffList'])
    ->name('admin.staff.list')
    ->middleware(['auth', 'can:admin']);

// スタッフ別勤怠一覧
Route::get('/admin/attendance/staff/{id}', [AttendanceController::class, 'adminAttendanceStaff'])
    ->name('admin.attendance.staff')
    ->middleware(['auth', 'can:admin']);

//出勤
Route::post('/work/start', [AttendanceController::class, 'start'])->name('work.start');

//退勤
Route::post('/work/end', [AttendanceController::class, 'end'])->name('work.end');

//休憩開始
Route::post('/rest/start', [AttendanceController::class, 'rest'])->name('rest.start');

//休憩終了
Route::post('/rest/end', [AttendanceController::class, 'restEnd'])->name('rest.end');

// 管理者用ログイン画面
Route::get('/admin/login', fn() => view('admin_login'))
    ->name('admin.login');

Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
    ->name('admin.login.submit');

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

// Admin専用の承認画面表示
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{id}', [AttendanceController::class, 'showApprovePage'])
        ->name('admin.request.approve.show');

    Route::post('/request/approve/{id}', [AttendanceController::class, 'approveCorrectionRequest'])
        ->name('admin.request.approve');
});
