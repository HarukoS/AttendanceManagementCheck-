<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkStartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 出勤ボタンが正しく機能する
     */
    public function work_start_button_works_correctly()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 13, 9, 0));

        // テスト手順1: ステータスが勤務外のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 0,
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順2: 画面に「出勤」ボタンが表示されていることを確認する
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('出勤');

        // テスト手順3: 出勤の処理を行う
        $this->post(route('work.start'))
            ->assertRedirect();

        // 期待挙動:画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「勤務中」になる
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 1,
        ]);
    }

    /**
     * @test
     * 出勤は一日一回のみできる
     */
    public function work_start_button_is_not_displayed_after_finished()
    {
        // テスト手順1: ステータスが退勤済であるユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 3,
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順2: 勤務ボタンが表示されないことを確認する
        // 期待挙動: 画面上に「出勤」ボタンが表示されない
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertDontSee('出勤');
    }

    /**
     * @test
     * 出勤時刻が勤怠一覧画面で確認できる
     */
    public function work_start_time_is_displayed_on_attendance_list()
    {
        $fixedNow = Carbon::create(2026, 1, 13, 9, 15);
        Carbon::setTestNow($fixedNow);

        // テスト手順1: ステータスが勤務外のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 0,
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順2: 出勤の処理を行う
        $this->post(route('work.start'))
            ->assertRedirect();

        // テスト手順3: 勤怠一覧画面から出勤の日付を確認する
        // 期待挙動: 勤怠一覧画面に出勤時刻が正確に記録されている
        $this->get(route('attendance.list'))
            ->assertStatus(200)
            ->assertSee($fixedNow->format('H:i'));
    }
}
