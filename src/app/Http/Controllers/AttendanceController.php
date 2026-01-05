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
    public function attendance()
    {
        $user = Auth::user();
        $now = Carbon::now();

        return view('attendance', compact('user', 'now'));
    }

    public function start(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $now = Carbon::now();

        // 1. works テーブルに新規作成
        Work::create([
            'user_id' => $user->id,
            'date' => $now->toDateString(),
            'work_start' => $now->toTimeString(),
        ]);

        // 2. users テーブルの status を 1 に更新
        $user->status = 1;
        $user->save();

        return redirect()->back();
    }

    public function end(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $now = Carbon::now();

        // 当日の work レコードを取得
        $work = Work::where('user_id', $user->id)
            ->where('date', $now->toDateString())
            ->first();

        if ($work) {
            // work_end に現在時刻を保存
            $work->work_end = $now->toTimeString();
            $work->save();

            // ユーザーの status を退勤済みに変更
            $user->status = 3;
            $user->save();

            return redirect()->back();
        }
    }

    public function rest(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 今日の勤務データを取得（まだ退勤していないもの）
        $work = Work::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        // rests テーブルに休憩開始を登録
        Rest::create([
            'work_id'   => $work->id,
            'rest_start' => Carbon::now()->format('H:i:s'),
        ]);

        // user の status を休憩中（2）へ変更
        $user->status = 2;
        $user->save();

        return redirect()->back();
    }

    public function restEnd()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 今日の勤務データを取得
        $work = Work::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        // 今日の勤務の、休憩終了していない（rest_end が null）休憩の最後の1件を取得
        $rest = Rest::where('work_id', $work->id)
            ->whereNull('rest_end')
            ->latest()
            ->first();

        // 休憩終了時間を保存
        $rest->update([
            'rest_end' => Carbon::now()->format('H:i:s'),
        ]);

        // user の status を出勤中（1）へ戻す
        $user->status = 1;
        $user->save();

        return redirect()->back();
    }

    //ユーザー毎勤怠表ページ
    public function attendanceList(Request $request)
    {
        $targetMonth = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        $start = $targetMonth->copy()->startOfMonth();
        $end   = $targetMonth->copy()->endOfMonth();

        // 勤怠取得（date は date 型なので whereBetween でOK）
        $works = Work::with(['rests', 'requests.details'])
            ->where('user_id', Auth::id())
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn($w) => $w->date->format('Y-m-d'));

        // 修正後データを反映したカレンダー用配列
        $days = [];
        foreach ($start->daysUntil($end) as $date) {
            $work = $works[$date->format('Y-m-d')] ?? null;

            if ($work) {
                // 承認済みの最新申請がある場合
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
                                    // 新規休憩（start）
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
                                    // 新規休憩（end）
                                    $last = collect($newRestBuffer)->last();
                                    if ($last) {
                                        $last->rest_end = $detail->new_time;
                                    }
                                }
                                break;
                        }
                    }

                    // 新規休憩を rests に追加
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

    public function attendanceDetail(Request $request, $id)
    {
        $user = Auth::user();

        $work = Work::with(['rests', 'requests.details'])->findOrFail($id);

        // 権限制御
        if ($user->role !== 'admin' && $work->user_id !== $user->id) {
            abort(403);
        }

        // 承認待ち（最優先）
        $pendingRequest = $work->requests
            ->where('status', 0)
            ->sortByDesc('requested_at')
            ->first();

        // 承認済み（次点）
        $approvedRequest = $work->requests
            ->where('status', 1)
            ->sortByDesc('approved_at')
            ->first();

        /* ===========================
     * 表示用 rests をコピー
     * =========================== */
        $displayRests = $work->rests
            ->keyBy('id')
            ->map(function ($rest) {
                return (object)[
                    'id'         => $rest->id,
                    'rest_start' => $rest->rest_start,
                    'rest_end'   => $rest->rest_end,
                ];
            });

        /* ===========================
     * 表示用データ（初期）
     * =========================== */
        $display = [
            'work_start' => $work->work_start,
            'work_end'   => $work->work_end,
            'rests'      => $displayRests,
            'reason'     => $work->reason,
            'is_pending' => false,
        ];

        /* ===========================
     * pending → approved の順で反映
     * =========================== */
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

        // =============================
        // ④ コレクションのキーを数値にリセット
        // =============================
        $display['rests'] = $display['rests']->values();

        return view('attendance_detail', compact(
            'user',
            'work',
            'display'
        ));
    }

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

        /* ======================
     * 出勤・退勤
     * ====================== */
        $this->storeWorkDiff($request, $work, $correctionRequest);

        /* ======================
     * 既存休憩
     * ====================== */
        foreach ($work->rests as $index => $rest) {
            $this->storeRestDiff($request, $rest, $correctionRequest, $index);
        }

        /* ======================
     * ★ 新規休憩（ここが重要）
     * ====================== */
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
        // 出勤
        $oldStart = optional($work->work_start)?->format('H:i');
        $newStart = $request->input('work_start');

        if ($newStart !== null && $newStart !== $oldStart) {
            RequestDetail::create([
                'request_id' => $correctionRequest->id,
                'type'       => 'work_start',
                'work_id'    => $work->id,   // ★
                'old_time'   => $oldStart,   // ★
                'new_time'   => $newStart,   // ★
            ]);
        }

        // 退勤
        $oldEnd = optional($work->work_end)?->format('H:i');
        $newEnd = $request->input('work_end');

        if ($newEnd !== null && $newEnd !== $oldEnd) {
            RequestDetail::create([
                'request_id' => $correctionRequest->id,
                'type'       => 'work_end',
                'work_id'    => $work->id,   // ★
                'old_time'   => $oldEnd,     // ★
                'new_time'   => $newEnd,     // ★
            ]);
        }
    }

    private function storeRestDiff(
        Request $request,
        Rest $rest,
        CorrectionRequest $correctionRequest
    ) {
        // ★ Blade と完全一致
        $input = $request->input("rests.{$rest->id}");

        if (! $input) {
            return;
        }

        // 休憩開始
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

        // 休憩終了
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

    private function storeNewRest(
        Request $request,
        CorrectionRequest $correctionRequest
    ) {
        $input = $request->input('rests.new');

        if (
            empty($input['rest_start']) &&
            empty($input['rest_end'])
        ) {
            return;
        }

        RequestDetail::create([
            'request_id'     => $correctionRequest->id,
            'type'           => 'rest_new',
            'new_rest_start' => $input['rest_start'] ?? null,
            'new_rest_end'   => $input['rest_end'] ?? null,
        ]);
    }

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

    public function adminAttendanceList(Request $request)
    {
        // 表示対象日（なければ今日）
        $today = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        // 前日・翌日
        $prevDay = $today->copy()->subDay()->toDateString();
        $nextDay = $today->copy()->addDay()->toDateString();

        // 勤怠取得（requests.details も必ず eager load）
        $works = Work::with(['user', 'rests', 'requests.details'])
            ->whereDate('date', $today)
            ->get();

        foreach ($works as $work) {

            // 承認済み最新申請
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
                            // 新規休憩 start
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
                            // 新規休憩 end
                            $last = collect($newRestBuffer)->last();
                            if ($last) {
                                $last->rest_end = $detail->new_time;
                            }
                        }
                        break;
                }
            }

            // 新規休憩を rests に追加
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

    public function adminAttendanceDetail(Request $request, $id)
    {
        // ===========================
        // 管理者専用
        // ===========================
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $work = Work::with(['user', 'rests', 'requests.details'])->findOrFail($id);
        $user = $work->user;

        // ===========================
        // 申請の取得
        // ===========================
        // 承認待ち（最優先）
        $pendingRequest = $work->requests
            ->where('status', 0)
            ->sortByDesc('requested_at')
            ->first();

        // 承認済み（次点）
        $approvedRequest = $work->requests
            ->where('status', 1)
            ->sortByDesc('approved_at')
            ->first();

        // ===========================
        // 表示用 rests をコピー
        // ===========================
        $displayRests = $work->rests
            ->keyBy('id')
            ->map(function ($rest) {
                return (object)[
                    'id'         => $rest->id,
                    'rest_start' => $rest->rest_start,
                    'rest_end'   => $rest->rest_end,
                ];
            });

        // ===========================
        // 表示用データ（初期値）
        // ===========================
        $display = [
            'work_start' => $work->work_start,
            'work_end'   => $work->work_end,
            'rests'      => $displayRests,
            'reason'     => $work->reason,
            'is_pending' => false,
        ];

        // ===========================
        // pending → approved の順で反映
        // ===========================
        $targetRequest = $pendingRequest ?? $approvedRequest;

        if ($targetRequest) {

            // 新規休憩は 1ペアずつ処理する
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

        // =============================
        // ④ コレクションのキーを数値にリセット
        // =============================
        $display['rests'] = $display['rests']->values();

        return view('admin_attendance_detail', compact(
            'user',
            'work',
            'display'
        ));
    }

    // 承認画面表示
    public function showApprovePage($id)
    {
        $user = Auth::user();
        if ($user->role !== 'admin') {
            abort(403);
        }

        // CorrectionRequest + 関連データ取得
        $request = CorrectionRequest::with(['work.rests', 'details', 'user'])->findOrFail($id);
        $work = $request->work;

        // =============================
        // ① 既存休憩をコピー
        // =============================
        $displayRests = $work->rests
            ->map(function ($rest) {
                return (object)[
                    'id'         => $rest->id,
                    'rest_start' => $rest->rest_start,
                    'rest_end'   => $rest->rest_end,
                ];
            });

        // =============================
        // ② 初期表示データ
        // =============================
        $display = [
            'work_start' => $work->work_start,
            'work_end'   => $work->work_end,
            'rests'      => $displayRests,
            'reason'     => $request->reason,
        ];

        // =============================
        // ③ request_details の反映
        // =============================
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
                        // 既存休憩の start を上書き
                        $restObj = $display['rests']->firstWhere('id', $detail->rest_id);
                        if ($restObj) {
                            $restObj->rest_start = $time;
                        }
                    } else {
                        // 新規休憩 start（まだ push しない）
                        $newRestBuffer = (object)[
                            'id'         => null,
                            'rest_start' => $time,
                            'rest_end'   => null,
                        ];
                    }
                    break;

                case 'rest_end':
                    if ($detail->rest_id) {
                        // 既存休憩の end を上書き
                        $restObj = $display['rests']->firstWhere('id', $detail->rest_id);
                        if ($restObj) {
                            $restObj->rest_end = $time;
                        }
                    } elseif ($newRestBuffer) {
                        // 新規休憩 end → push
                        $newRestBuffer->rest_end = $time;
                        $display['rests']->push($newRestBuffer);
                        $newRestBuffer = null;
                    }
                    break;
            }
        }

        // =============================
        // ④ コレクションのキーを数値にリセット
        // =============================
        $display['rests'] = $display['rests']->values();

        return view('approve', compact('request', 'display'));
    }

    // 承認処理
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

    // スタッフ一覧画面
    public function staffList(Request $request)
    {
        $users = User::where('role', '!=', 'admin')->get();

        return view('staff_list', compact('users'));
    }

    // スタッフ別勤怠一覧
    public function adminAttendanceStaff(Request $request, $id)
    {
        // 対象スタッフ
        $staff = User::findOrFail($id);

        // 表示対象月（YYYY-MM）
        $targetMonth = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
            : now()->startOfMonth();

        // 前月・翌月
        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        // 月初〜月末
        $start = $targetMonth->copy()->startOfMonth();
        $end   = $targetMonth->copy()->endOfMonth();

        // 対象スタッフの勤怠取得
        $works = Work::with('rests')
            ->where('user_id', $staff->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn($w) => $w->date->format('Y-m-d'));

        // カレンダー用配列
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
}
