<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Work;
use App\Models\Rest;
use Carbon\Carbon;
use App\Models\Request as CorrectionRequest;
use App\Models\RequestDetail;
use App\Http\Requests\RequestRequest;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面表示
     */
    public function attendance()
    {
        $user = Auth::user();
        $now = Carbon::now();

        return view('attendance', compact('user', 'now'));
    }

    /**
     * 出勤
     */
    public function start(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $now = Carbon::now();

        Work::create([
            'user_id' => $user->id,
            'date' => $now->toDateString(),
            'work_start' => $now->toTimeString(),
        ]);

        $user->status = 1;
        $user->save();

        return redirect()->back();
    }

    /**
     * 退勤
     */
    public function end(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $now = Carbon::now();

        $work = Work::where('user_id', $user->id)
            ->where('date', $now->toDateString())
            ->first();

        if ($work) {
            $work->work_end = $now->toTimeString();
            $work->save();

            $user->status = 3;
            $user->save();

            return redirect()->back();
        }
    }

    /**
     * 休憩入
     */
    public function rest(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $work = Work::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        Rest::create([
            'work_id'   => $work->id,
            'rest_start' => Carbon::now()->format('H:i:s'),
        ]);

        $user->status = 2;
        $user->save();

        return redirect()->back();
    }

    /**
     * 休憩戻
     */
    public function restEnd()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $work = Work::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        $rest = Rest::where('work_id', $work->id)
            ->whereNull('rest_end')
            ->latest()
            ->first();

        $rest->update([
            'rest_end' => Carbon::now()->format('H:i:s'),
        ]);

        $user->status = 1;
        $user->save();

        return redirect()->back();
    }

    //勤怠一覧表示
    public function attendanceList(Request $request)
    {
        $targetMonth = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        $start = $targetMonth->copy()->startOfMonth();
        $end   = $targetMonth->copy()->endOfMonth();

        $works = Work::with(['rests', 'requests.details'])
            ->where('user_id', Auth::id())
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn($w) => $w->date->format('Y-m-d'));

        $days = [];
        foreach ($start->daysUntil($end) as $date) {
            $work = $works[$date->format('Y-m-d')] ?? null;

            if ($work) {
                $approvedRequest = $work->requests
                    ->where('status', 1)
                    ->sortByDesc('approved_at')
                    ->first();

                if ($approvedRequest) {

                    $newRestBuffer = [];

                    foreach ($approvedRequest->details as $detail) {
                        switch ($detail->type) {

                            case 'work_start':
                                $work->work_start = $detail->new_time;
                                break;

                            case 'work_end':
                                $work->work_end = $detail->new_time;
                                break;

                            case 'rest_start':
                                if ($detail->rest_id) {
                                    $rest = $work->rests->firstWhere('id', $detail->rest_id);
                                    if ($rest) $rest->rest_start = $detail->new_time;
                                } else {
                                    $newRestBuffer[] = (object)[
                                        'rest_start' => $detail->new_time,
                                        'rest_end'   => null,
                                    ];
                                }
                                break;

                            case 'rest_end':
                                if ($detail->rest_id) {
                                    $rest = $work->rests->firstWhere('id', $detail->rest_id);
                                    if ($rest) $rest->rest_end = $detail->new_time;
                                } else {
                                    $last = collect($newRestBuffer)->last();
                                    if ($last) {
                                        $last->rest_end = $detail->new_time;
                                    }
                                }
                                break;
                        }
                    }

                    foreach ($newRestBuffer as $newRest) {
                        $work->rests->push($newRest);
                    }
                }
            }

            $days[] = [
                'date' => $date,
                'work' => $work,
            ];
        }

        return view('attendance_list', compact(
            'targetMonth',
            'prevMonth',
            'nextMonth',
            'days'
        ));
    }

    /**
     * 勤怠詳細画面表示
     */
    public function attendanceDetail(Request $request, $id)
    {
        $user = Auth::user();

        $work = Work::with(['rests', 'requests.details'])->findOrFail($id);

        if ($user->role !== 'admin' && $work->user_id !== $user->id) {
            abort(403);
        }

        $pendingRequest = $work->requests
            ->where('status', 0)
            ->sortByDesc('requested_at')
            ->first();

        $approvedRequest = $work->requests
            ->where('status', 1)
            ->sortByDesc('approved_at')
            ->first();

        $displayRests = $work->rests
            ->keyBy('id')
            ->map(function ($rest) {
                return (object)[
                    'id'         => $rest->id,
                    'rest_start' => $rest->rest_start,
                    'rest_end'   => $rest->rest_end,
                ];
            });

        $display = [
            'work_start' => $work->work_start,
            'work_end'   => $work->work_end,
            'rests'      => $displayRests,
            'reason'     => $work->reason,
            'is_pending' => false,
        ];

        $targetRequest = $pendingRequest ?? $approvedRequest;

        if ($targetRequest) {

            $newRest = null;

            foreach ($targetRequest->details as $detail) {

                $time = Carbon::createFromFormat('H:i:s', $detail->new_time);

                switch ($detail->type) {

                    case 'work_start':
                        $display['work_start'] = $time;
                        break;

                    case 'work_end':
                        $display['work_end'] = $time;
                        break;

                    case 'rest_start':
                        if ($detail->rest_id && isset($display['rests'][$detail->rest_id])) {
                            $display['rests'][$detail->rest_id]->rest_start = $time;
                        } else {
                            $newRest = (object)[
                                'id' => null,
                                'rest_start' => $time,
                                'rest_end' => null,
                            ];
                        }
                        break;

                    case 'rest_end':
                        if ($detail->rest_id && isset($display['rests'][$detail->rest_id])) {
                            $display['rests'][$detail->rest_id]->rest_end = $time;
                        } elseif ($newRest) {
                            $newRest->rest_end = $time;
                            $display['rests']->push($newRest);
                            $newRest = null;
                        }
                        break;
                }
            }

            $display['reason'] = $targetRequest->reason;
            $display['is_pending'] = $targetRequest->status === 0;
        }

        $display['rests'] = $display['rests']->values();

        return view('attendance_detail', compact(
            'user',
            'work',
            'display'
        ));
    }

    /**
     *勤怠の修正申請
     */
    public function storeCorrectionRequest(RequestRequest $request, Work $work)
    {
        $user = Auth::user();

        $status = $user->role === 'admin' ? 1 : 0;
        $approvedBy = $status ? $user->id : null;
        $approvedAt = $status ? now() : null;

        if ($status === 0 && $work->requests()->where('status', 0)->exists()) {
            return back()->withErrors('すでに申請中です');
        }

        $correctionRequest = CorrectionRequest::create([
            'user_id'      => $user->id,
            'work_id'      => $work->id,
            'status'       => $status,
            'approved_by'  => $approvedBy,
            'approved_at'  => $approvedAt,
            'reason'       => $request->input('reason'),
            'requested_at' => now(),
        ]);

        $this->storeWorkDiff($request, $work, $correctionRequest);

        foreach ($work->rests as $index => $rest) {
            $this->storeRestDiff($request, $rest, $correctionRequest, $index);
        }

        $newRest = $request->input('rests.new');

        if (!empty($newRest['rest_start']) || !empty($newRest['rest_end'])) {

            if (!empty($newRest['rest_start'])) {
                RequestDetail::create([
                    'request_id' => $correctionRequest->id,
                    'type'       => 'rest_start',
                    'rest_id'    => null,
                    'old_time'   => null,
                    'new_time'   => $newRest['rest_start'],
                ]);
            }

            if (!empty($newRest['rest_end'])) {
                RequestDetail::create([
                    'request_id' => $correctionRequest->id,
                    'type'       => 'rest_end',
                    'rest_id'    => null,
                    'old_time'   => null,
                    'new_time'   => $newRest['rest_end'],
                ]);
            }
        }

        return redirect()
            ->route(
                $user->role === 'admin'
                    ? 'admin.attendance.detail'
                    : 'attendance.detail',
                $work->id
            )
            ->with(
                'message',
                $status === 1
                    ? 'Admin操作のため自動承認しました'
                    : '修正申請を送信しました'
            );
    }

    private function storeWorkDiff(
        Request $request,
        Work $work,
        CorrectionRequest $correctionRequest
    ) {
        $oldStart = optional($work->work_start)?->format('H:i');
        $newStart = $request->input('work_start');

        if ($newStart !== null && $newStart !== $oldStart) {
            RequestDetail::create([
                'request_id' => $correctionRequest->id,
                'type'       => 'work_start',
                'work_id'    => $work->id,
                'old_time'   => $oldStart,
                'new_time'   => $newStart,
            ]);
        }

        $oldEnd = optional($work->work_end)?->format('H:i');
        $newEnd = $request->input('work_end');

        if ($newEnd !== null && $newEnd !== $oldEnd) {
            RequestDetail::create([
                'request_id' => $correctionRequest->id,
                'type'       => 'work_end',
                'work_id'    => $work->id,
                'old_time'   => $oldEnd,
                'new_time'   => $newEnd,
            ]);
        }
    }

    private function storeRestDiff(
        Request $request,
        Rest $rest,
        CorrectionRequest $correctionRequest
    ) {
        $input = $request->input("rests.{$rest->id}");

        if (! $input) {
            return;
        }

        $oldStart = optional($rest->rest_start)?->format('H:i');
        $newStart = $input['rest_start'] ?? null;

        if ($newStart !== null && $newStart !== $oldStart) {
            RequestDetail::create([
                'request_id' => $correctionRequest->id,
                'type'       => 'rest_start',
                'rest_id'    => $rest->id,
                'old_time'   => $oldStart,
                'new_time'   => $newStart,
            ]);
        }

        $oldEnd = optional($rest->rest_end)?->format('H:i');
        $newEnd = $input['rest_end'] ?? null;

        if ($newEnd !== null && $newEnd !== $oldEnd) {
            RequestDetail::create([
                'request_id' => $correctionRequest->id,
                'type'       => 'rest_end',
                'rest_id'    => $rest->id,
                'old_time'   => $oldEnd,
                'new_time'   => $newEnd,
            ]);
        }
    }

    /**
     * 申請一覧画面表示
     */
    public function userStampCorrectionList(Request $request)
    {
        $status = (int) $request->query('status', 0);
        $user = Auth::user();

        $query = CorrectionRequest::with(['user', 'work']);

        if ($user->role === 'admin') {
            // Adminの場合
            if ($status === 1) {
                // 承認済みを表示する場合、自分が申請したものは除外
                $query->where('status', 1)
                    ->where('user_id', '!=', $user->id);
            } else {
                // 承認待ちはそのまま取得
                $query->where('status', 0);
            }
        } else {
            // Admin以外は自分の申請だけ
            $query->where('status', $status)
                ->where('user_id', $user->id);
        }

        $requests = $query->orderByDesc('requested_at')->get();

        return view('stamp_correction_request', compact('requests', 'status'));
    }

    /**
     * 管理者用日時勤怠一覧表示
     */
    public function adminAttendanceList(Request $request)
    {
        $today = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        $prevDay = $today->copy()->subDay()->toDateString();
        $nextDay = $today->copy()->addDay()->toDateString();

        $works = Work::with(['user', 'rests', 'requests.details'])
            ->whereDate('date', $today)
            ->get();

        foreach ($works as $work) {

            $approvedRequest = $work->requests
                ->where('status', 1)
                ->sortByDesc('approved_at')
                ->first();

            if (! $approvedRequest) {
                continue;
            }

            $newRestBuffer = [];

            foreach ($approvedRequest->details as $detail) {
                switch ($detail->type) {

                    case 'work_start':
                        $work->work_start = $detail->new_time;
                        break;

                    case 'work_end':
                        $work->work_end = $detail->new_time;
                        break;

                    case 'rest_start':
                        if ($detail->rest_id) {
                            $rest = $work->rests->firstWhere('id', $detail->rest_id);
                            if ($rest) {
                                $rest->rest_start = $detail->new_time;
                            }
                        } else {
                            $newRestBuffer[] = (object)[
                                'rest_start' => $detail->new_time,
                                'rest_end'   => null,
                            ];
                        }
                        break;

                    case 'rest_end':
                        if ($detail->rest_id) {
                            $rest = $work->rests->firstWhere('id', $detail->rest_id);
                            if ($rest) {
                                $rest->rest_end = $detail->new_time;
                            }
                        } else {
                            $last = collect($newRestBuffer)->last();
                            if ($last) {
                                $last->rest_end = $detail->new_time;
                            }
                        }
                        break;
                }
            }

            foreach ($newRestBuffer as $newRest) {
                $work->rests->push($newRest);
            }
        }

        return view('admin_attendance_list', compact(
            'works',
            'today',
            'prevDay',
            'nextDay'
        ));
    }

    /**
     * 管理者用勤怠詳細画面表示
     */
    public function adminAttendanceDetail(Request $request, $id)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $work = Work::with(['user', 'rests', 'requests.details'])->findOrFail($id);
        $user = $work->user;

        $pendingRequest = $work->requests
            ->where('status', 0)
            ->sortByDesc('requested_at')
            ->first();

        $approvedRequest = $work->requests
            ->where('status', 1)
            ->sortByDesc('approved_at')
            ->first();

        $displayRests = $work->rests
            ->keyBy('id')
            ->map(function ($rest) {
                return (object)[
                    'id'         => $rest->id,
                    'rest_start' => $rest->rest_start,
                    'rest_end'   => $rest->rest_end,
                ];
            });

        $display = [
            'work_start' => $work->work_start,
            'work_end'   => $work->work_end,
            'rests'      => $displayRests,
            'reason'     => $work->reason,
            'is_pending' => false,
        ];

        $targetRequest = $pendingRequest ?? $approvedRequest;

        if ($targetRequest) {

            $newRest = null;

            foreach ($targetRequest->details as $detail) {
                $time = Carbon::parse($detail->new_time);

                switch ($detail->type) {

                    case 'work_start':
                        $display['work_start'] = $time;
                        break;

                    case 'work_end':
                        $display['work_end'] = $time;
                        break;

                    case 'rest_start':
                        if ($detail->rest_id && isset($display['rests'][$detail->rest_id])) {
                            $display['rests'][$detail->rest_id]->rest_start = $time;
                        } else {
                            $newRest = (object)[
                                'id' => null,
                                'rest_start' => $time,
                                'rest_end' => null,
                            ];
                        }
                        break;

                    case 'rest_end':
                        if ($detail->rest_id && isset($display['rests'][$detail->rest_id])) {
                            $display['rests'][$detail->rest_id]->rest_end = $time;
                        } elseif ($newRest) {
                            $newRest->rest_end = $time;
                            $display['rests']->push($newRest);
                            $newRest = null;
                        }
                        break;
                }
            }

            $display['reason']     = $targetRequest->reason;
            $display['is_pending'] = $targetRequest->status === 0;
        }

        $display['rests'] = $display['rests']->values();

        return view('admin_attendance_detail', compact(
            'user',
            'work',
            'display'
        ));
    }

    /**
     * 管理者用承認画面表示
     */
    public function showApprovePage($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            abort(403);
        }

        $request = CorrectionRequest::with(['work.rests', 'details', 'user'])->findOrFail($id);
        $work = $request->work;

        $displayRests = $work->rests
            ->map(function ($rest) {
                return (object)[
                    'id'         => $rest->id,
                    'rest_start' => $rest->rest_start,
                    'rest_end'   => $rest->rest_end,
                ];
            });

        $display = [
            'work_start' => $work->work_start,
            'work_end'   => $work->work_end,
            'rests'      => $displayRests,
            'reason'     => $request->reason,
        ];

        $newRestBuffer = null;

        foreach ($request->details as $detail) {
            $time = Carbon::parse($detail->new_time);

            switch ($detail->type) {
                case 'work_start':
                    $display['work_start'] = $time;
                    break;

                case 'work_end':
                    $display['work_end'] = $time;
                    break;

                case 'rest_start':
                    if ($detail->rest_id) {
                        $restObj = $display['rests']->firstWhere('id', $detail->rest_id);
                        if ($restObj) {
                            $restObj->rest_start = $time;
                        }
                    } else {
                        $newRestBuffer = (object)[
                            'id'         => null,
                            'rest_start' => $time,
                            'rest_end'   => null,
                        ];
                    }
                    break;

                case 'rest_end':
                    if ($detail->rest_id) {
                        $restObj = $display['rests']->firstWhere('id', $detail->rest_id);
                        if ($restObj) {
                            $restObj->rest_end = $time;
                        }
                    } elseif ($newRestBuffer) {
                        $newRestBuffer->rest_end = $time;
                        $display['rests']->push($newRestBuffer);
                        $newRestBuffer = null;
                    }
                    break;
            }
        }

        $display['rests'] = $display['rests']->values();

        return view('approve', compact('request', 'display'));
    }

    /**
     * 管理者用承認機能
     */
    public function approveCorrectionRequest($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            abort(403);
        }

        $request = CorrectionRequest::findOrFail($id);

        $request->status = 1;
        $request->approved_by = $user->id;
        $request->approved_at = now();
        $request->save();

        return redirect()->route('admin.request.approve.show', ['id' => $request->id]);
    }

    /**
     * 管理者用スタッフ一覧画面表示
     */
    public function staffList(Request $request)
    {
        $users = User::where('role', '!=', 'admin')->get();

        return view('staff_list', compact('users'));
    }

    /**
     * 管理者用スタッフ別月次勤怠一覧表示
     */
    public function adminAttendanceStaff(Request $request, $id)
    {
        $staff = User::findOrFail($id);

        $targetMonth = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        $start = $targetMonth->copy()->startOfMonth();
        $end   = $targetMonth->copy()->endOfMonth();

        $works = Work::with('rests')
            ->where('user_id', $staff->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn($w) => $w->date->format('Y-m-d'));

        $days = [];
        foreach ($start->daysUntil($end) as $date) {
            $days[] = [
                'date' => $date,
                'work' => $works[$date->format('Y-m-d')] ?? null,
            ];
        }

        return view('attendance_staff', compact(
            'staff',
            'targetMonth',
            'prevMonth',
            'nextMonth',
            'days'
        ));
    }

    /**
     * スタッフ毎月次勤怠CSV出力
     */
    public function adminAttendanceStaffCsv(Request $request, $id)
    {
        $staff = User::findOrFail($id);

        $targetMonth = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $start = $targetMonth->copy()->startOfMonth();
        $end   = $targetMonth->copy()->endOfMonth();

        $works = Work::with('rests')
            ->where('user_id', $staff->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        $fileName = "{$staff->name}_{$targetMonth->format('Y_m')}_attendance.csv";

        return response()->streamDownload(function () use ($works) {
            $handle = fopen('php://output', 'w');

            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '日付',
                '出勤',
                '退勤',
                '休憩時間',
                '労働時間',
            ]);

            foreach ($works as $work) {
                fputcsv($handle, [
                    $work->date->format('Y-m-d'),
                    $work->work_start
                        ? Carbon::parse($work->work_start)->format('H:i')
                        : '',
                    $work->work_end
                        ? Carbon::parse($work->work_end)->format('H:i')
                        : '',
                    $work->work_end
                        ? sprintf(
                            '%02d:%02d',
                            floor($work->getRestMinutes() / 60),
                            $work->getRestMinutes() % 60
                        )
                        : '',
                    $work->work_end
                        ? sprintf(
                            '%02d:%02d',
                            floor($work->getActualMinutes() / 60),
                            $work->getActualMinutes() % 60
                        )
                        : '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
