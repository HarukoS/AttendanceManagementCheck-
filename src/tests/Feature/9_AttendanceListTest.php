<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 自分が行った勤怠情報が全て表示されている
     */
    public function user_can_see_all_of_own_attendance_records()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 10));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        // 自分の勤怠データを2日分作成
        $work1 = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-08',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        $work2 = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-09',
            'work_start' => '10:00:00',
            'work_end'   => '19:00:00',
        ]);

        // テスト手順1: 勤怠情報が登録されたユーザーにログインする
        $this->actingAs($user);

        // テスト手順2: 勤怠一覧ページを開く
        // テスト手順3: 自分の勤怠情報がすべて表示されていることを確認する
        // 期待挙動: 自分の勤怠情報が全て表示されている
        $this->get(route('attendance.list'))
            ->assertStatus(200)
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('10:00')
            ->assertSee('19:00');
    }

    /**
     * @test
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function current_month_is_displayed_on_attendance_list()
    {
        $now = Carbon::create(2026, 1, 15);
        Carbon::setTestNow($now);

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        // テスト手順1: ユーザーにログインする
        $this->actingAs($user);

        // テスト手順2: 勤怠一覧ページを開く
        // 期待挙動: 現在の月が表示されている
        $this->get(route('attendance.list'))
            ->assertStatus(200)
            ->assertSee($now->format('Y/m'));
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function previous_month_attendance_is_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        // 前月（2025年12月）の勤怠
        Work::create([
            'user_id'    => $user->id,
            'date'       => '2025-12-20',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1: 勤怠情報が登録されたユーザーにログインする
        $this->actingAs($user);

        // テスト手順2: 勤怠一覧ページを開く
        // テスト手順3: 「前月」ボタンを押す
        // 期待挙動: 前月の情報が表示されている
        $this->get(route('attendance.list', ['month' => '2025-12']))
            ->assertStatus(200)
            ->assertSee('2025/12')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function next_month_attendance_is_displayed()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        // 翌月（2026年2月）の勤怠
        Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-02-05',
            'work_start' => '08:30:00',
            'work_end'   => '17:30:00',
        ]);

        // テスト手順1: 勤怠情報が登録されたユーザーにログインする
        $this->actingAs($user);

        // テスト手順2: 勤怠一覧ページを開く
        // テスト手順3: 「翌月」ボタンを押す
        // 期待挙動: 翌月の情報が表示されている
        $this->get(route('attendance.list', ['month' => '2026-02']))
            ->assertStatus(200)
            ->assertSee('2026/02')
            ->assertSee('08:30')
            ->assertSee('17:30');
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function user_can_navigate_to_attendance_detail_page()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 10));

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1: 勤怠情報が登録されたユーザーにログインする
        $this->actingAs($user);

        // テスト手順2: 勤怠一覧ページを開く
        // テスト手順3: 「詳細」ボタンを押す
        $this->get(route('attendance.list'))
            ->assertStatus(200)
            ->assertSee(route('attendance.detail', $work->id));

        // 期待挙動: その日の勤怠詳細画面に遷移する
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('09:00')
            ->assertSee('18:00');
    }
}
