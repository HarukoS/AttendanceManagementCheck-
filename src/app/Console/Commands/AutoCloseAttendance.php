<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Work;
use App\Models\Rest;
use Carbon\Carbon;

class AutoCloseAttendance extends Command
{
    protected $signature = 'attendance:auto-close';
    protected $description = '日付変更時に未完了の勤怠を自動クローズする';

    public function handle()
    {
        $yesterday = Carbon::yesterday()->toDateString();
        $closeTime = '00:00:00';

        // status が 1,2,3 のユーザーを対象
        $users = User::whereIn('status', [1, 2, 3])->get();

        foreach ($users as $user) {

            // status 1 or 2 → 勤務中 or 休憩中
            if (in_array($user->status, [1, 2])) {

                $work = Work::where('user_id', $user->id)
                    ->where('date', $yesterday)
                    ->whereNull('work_end')
                    ->first();

                if ($work) {
                    // 休憩中だった場合、未終了の休憩を閉じる
                    if ($user->status === 2) {
                        Rest::where('work_id', $work->id)
                            ->whereNull('rest_end')
                            ->update(['rest_end' => $closeTime]);
                    }

                    // 勤務をクローズ
                    $work->update([
                        'work_end' => $closeTime,
                    ]);
                }
            }

            // status 3（退勤後）も含めて初期状態へ
            $user->update(['status' => 0]);
        }

        return Command::SUCCESS;
    }
}