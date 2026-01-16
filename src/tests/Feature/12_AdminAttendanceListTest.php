<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Work;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function admin_can_view_all_users_attendance_for_today()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        $admin = User::factory()->create([
            'email' => 'admin@gmail.com',
            'role'  => 'admin',
        ]);

        $userA = User::factory()->create(['name' => '山田太郎']);
        $userB = User::factory()->create(['name' => '佐藤花子']);

        Work::create([
            'user_id'    => $userA->id,
            'date'       => today(),
            'work_start' => '09:00',
            'work_end'   => '18:00',
        ]);

        Work::create([
            'user_id'    => $userB->id,
            'date'       => today(),
            'work_start' => '10:00',
            'work_end'   => '19:00',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠一覧画面を開く
        //期待挙動: その日の全ユーザーの勤怠情報が正確な値になっている
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.list'))
            ->assertStatus(200)
            ->assertSee('山田太郎')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('佐藤花子')
            ->assertSee('10:00')
            ->assertSee('19:00');
    }

    /**
     * @test
     * 遷移した際に現在の日付が表示される
     */
    public function today_date_is_displayed_on_admin_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠一覧画面を開く
        //期待挙動: 勤怠一覧画面にその日の日付が表示されている
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.list'))
            ->assertStatus(200)
            ->assertSee('2026年01月15日の勤怠')
            ->assertSee('2026/01/15');
    }

    /**
     * @test
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function previous_day_attendance_is_displayed_when_clicking_previous_button()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create(['name' => '前日ユーザー']);

        Work::create([
            'user_id'    => $user->id,
            'date'       => Carbon::yesterday(),
            'work_start' => '08:00',
            'work_end'   => '17:00',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠一覧画面を開く
        //テスト3: 「前日」ボタンを押す
        //期待挙動: 前日の日付の勤怠情報が表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.list', [
                'date' => Carbon::yesterday()->toDateString(),
            ]))
            ->assertStatus(200)
            ->assertSee('前日ユーザー')
            ->assertSee('2026年01月14日の勤怠');
    }

    /**
     * @test
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function next_day_attendance_is_displayed_when_clicking_next_button()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create(['name' => '翌日ユーザー']);

        Work::create([
            'user_id'    => $user->id,
            'date'       => Carbon::tomorrow(),
            'work_start' => '11:00',
            'work_end'   => '20:00',
        ]);

        //テスト1: 管理者ユーザーにログインする
        //テスト2: 勤怠一覧画面を開く
        //テスト3: 「翌日」ボタンを押す
        //期待挙動: 翌日の日付の勤怠情報が表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.list', [
                'date' => Carbon::tomorrow()->toDateString(),
            ]))
            ->assertStatus(200)
            ->assertSee('翌日ユーザー')
            ->assertSee('2026年01月16日の勤怠');
    }
}
