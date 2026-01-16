<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use App\Models\Rest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function admin_can_view_selected_attendance_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        Rest::create([
            'work_id'    => $work->id,
            'rest_start' => '12:00:00',
            'rest_end'   => '13:00:00',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠詳細画面を開く
        //期待挙動: 詳細画面の内容が選択した情報と一致する
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細')
            ->assertSee($user->name)
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('12:00')
            ->assertSee('13:00');
    }

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function work_start_after_work_end_shows_error_on_admin_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠詳細画面を開く
        //テスト3: 出勤時間を退勤時間より後に設定する
        //テスト4: 保存処理をする
        //期待挙動: 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->post(route('attendance.request.store', $work->id), [
                'work_start' => '19:00',
                'work_end'   => '18:00',
                'reason'     => '管理者修正',
            ])
            ->assertSessionHasErrors([
                'work_start' => '出勤時間もしくは退勤時間が不適切な値です',
            ]);
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function rest_start_after_work_end_shows_error_on_admin_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠詳細画面を開く
        //テスト3: 休憩開始時間を退勤時間より後に設定する
        //テスト4: 保存処理をする
        //期待挙動: 「休憩時間が不適切な値です」というバリデーションメッセージが表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->post(route('attendance.request.store', $work->id), [
                'work_start' => '09:00',
                'work_end'   => '18:00',
                'rests' => [
                    'new' => [
                        'rest_start' => '19:00',
                        'rest_end'   => '19:30',
                    ],
                ],
                'reason' => '管理者修正',
            ])
            ->assertSessionHasErrors([
                'rests.new.rest_start' => '休憩時間が不適切な値です',
            ]);
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function rest_end_after_work_end_shows_error_on_admin_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠詳細画面を開く
        //テスト3: 休憩終了時間を退勤時間より後に設定する
        //テスト4: 保存処理をする
        //期待挙動: 「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->post(route('attendance.request.store', $work->id), [
                'work_start' => '09:00',
                'work_end'   => '18:00',
                'rests' => [
                    'new' => [
                        'rest_start' => '17:30',
                        'rest_end'   => '19:00',
                    ],
                ],
                'reason' => '管理者修正',
            ])
            ->assertSessionHasErrors([
                'rests.new.rest_end' => '休憩時間もしくは退勤時間が不適切な値です',
            ]);
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function empty_reason_shows_error_on_admin_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠詳細画面を開く
        //テスト3: 備考欄を未入力のまま保存処理をする
        //期待挙動: 「備考を記入してください」というバリデーションメッセージが表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->post(route('attendance.request.store', $work->id), [
                'work_start' => '09:00',
                'work_end'   => '18:00',
                'reason'     => '',
            ])
            ->assertSessionHasErrors([
                'reason' => '備考を記入してください',
            ]);
    }
}
