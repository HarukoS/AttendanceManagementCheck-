<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use App\Models\Rest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 勤怠詳細画面の名前がログインユーザーの氏名になっている
     */
    public function detail_page_displays_logged_in_user_name()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => '山田 太郎',
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

        // テスト手順2: 勤怠詳細ページを開く
        // テスト手順3: 名前欄を確認する
        // 期待挙動: 名前がログインユーザーの名前になっている
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('山田 太郎');
    }

    /**
     * @test
     * 勤怠詳細画面の日付が選択した日付になっている
     */
    public function detail_page_displays_correct_date()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => Carbon::create(2026, 1, 10),
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1: 勤怠情報が登録されたユーザーにログインする
        $this->actingAs($user);

        // テスト手順2: 勤怠詳細ページを開く
        // テスト手順3: 日付欄を確認する
        // 期待挙動: 日付が選択した日付になっている
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('2026年')
            ->assertSee('1月10日');
    }

    /**
     * @test
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function detail_page_displays_correct_work_start_and_end_time()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:15:00',
            'work_end'   => '18:30:00',
        ]);

        // テスト手順1: 勤怠情報が登録されたユーザーにログインする
        $this->actingAs($user);

        // テスト手順2: 勤怠詳細ページを開く
        // テスト手順3: 出勤・退勤欄を確認する
        // 期待挙動: 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('09:15')
            ->assertSee('18:30');
    }

    /**
     * @test
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function detail_page_displays_correct_rest_time()
    {
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

        // 休憩データ
        Rest::create([
            'work_id'    => $work->id,
            'rest_start' => '12:00:00',
            'rest_end'   => '13:00:00',
        ]);

        // テスト手順1: 勤怠情報が登録されたユーザーにログインする
        $this->actingAs($user);

        // テスト手順2: 勤怠詳細ページを開く
        // テスト手順3: 休憩欄を確認する
        // 期待挙動: 「休憩」にて記されている時間がログインユーザーの打刻と一致している
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('12:00')
            ->assertSee('13:00');
    }
}
