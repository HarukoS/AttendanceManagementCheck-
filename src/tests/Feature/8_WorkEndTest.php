<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkEndTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 退勤ボタンが正しく機能する
     */
    public function work_end_button_works_correctly()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 13, 18, 0));

        // テスト手順1: ステータスが勤務中のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 1, // 勤務中
            'role' => 'user',
        ]);

        Work::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_start' => now()->subHours(8)->toTimeString(),
        ]);

        $this->actingAs($user);

        // テスト手順2: 画面に「退勤」ボタンが表示されていることを確認する
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('退勤');

        // テスト手順3: 退勤の処理を行う
        $this->post(route('work.end'))
            ->assertRedirect();

        // 期待挙動: 画面上に「退勤」ボタンが表示され、処理後に画面上に表示されるステータスが「退勤済」になる
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 3, // 退勤済
        ]);
    }

    /**
     * @test
     * 退勤時刻が勤怠一覧画面で確認できる
     */
    public function work_end_time_is_displayed_on_attendance_list()
    {
        $fixedStart = Carbon::create(2026, 1, 13, 9, 0);
        $fixedEnd   = Carbon::create(2026, 1, 13, 18, 15);

        Carbon::setTestNow($fixedStart);

        // テスト手順1: ステータスが勤務外のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 0, // 勤務外
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順2: 出勤と退勤の処理を行う
        $this->post(route('work.start'))->assertRedirect();

        Carbon::setTestNow($fixedEnd);

        $this->post(route('work.end'))->assertRedirect();

        // テスト手順3: 勤怠一覧画面から退勤時刻の日付を確認する
        // 期待挙動: 勤怠一覧画面に退勤時刻が正確に記録されている
        $this->get(route('attendance.list'))
            ->assertStatus(200)
            ->assertSee($fixedEnd->format('H:i'));
    }
}
