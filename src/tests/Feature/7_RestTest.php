<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;

class RestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    /**
     * @test
     * 休憩ボタンが正しく機能する
     */
    public function rest_start_button_works_correctly()
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        // テスト手順1: ステータスが出勤中のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 1,
        ]);

        Work::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_start' => now()->subHour()->toTimeString(),
        ]);

        $this->actingAs($user);

        // テスト手順2: 画面に「休憩入」ボタンが表示されていることを確認する
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩入');

        // テスト手順3: 休憩の処理を行う
        $this->post(route('rest.start'))->assertRedirect();

        // 期待挙動: 画面上に「休憩入」ボタンが表示され、処理後に画面上に表示されるステータスが「休憩中」になる
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 2, // 休憩中
        ]);

        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩中');
    }

    /**
     * @test
     * 休憩は一日に何回でもできる
     */
    public function rest_can_be_taken_multiple_times_per_day()
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        // テスト手順1: ステータスが出勤中であるユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $work = Work::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_start' => now()->subHour()->toTimeString(),
        ]);

        $this->actingAs($user);

        // テスト手順2: 休憩入と休憩戻の処理を行う
        $this->post(route('rest.start'));
        $this->post(route('rest.end'));

        // テスト手順3: 「休憩入」ボタンが表示されることを確認する
        // 期待挙動: 画面上に「休憩入」ボタンが表示される
        $this->get('/attendance')
            ->assertStatus(200)
            ->assertSee('休憩入');
    }

    /**
     * @test
     * 休憩戻ボタンが正しく機能する
     */
    public function rest_end_button_works_correctly()
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        // テスト手順1: ステータスが出勤中であるユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 1,
        ]);

        Work::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_start' => now()->subHour()->toTimeString(),
        ]);

        $this->actingAs($user);

        // テスト手順2: 休憩入の処理を行う
        $this->post(route('rest.start'));

        $this->get('/attendance')
            ->assertSee('休憩戻');

        // テスト手順3: 休憩戻の処理を行う
        $this->post(route('rest.end'))->assertRedirect();

        // 期待挙動: 休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更される
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'status' => 1,
        ]);
    }

    /**
     * @test
     * 休憩戻は一日に何回でもできる
     */
    public function rest_end_can_be_done_multiple_times()
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        // テスト手順1: ステータスが出勤中であるユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 1,
        ]);

        Work::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_start' => now()->subHour()->toTimeString(),
        ]);

        $this->actingAs($user);

        // テスト手順2: 休憩入と休憩戻の処理を行い、再度休憩入の処理を行う
        $this->post(route('rest.start'));
        $this->post(route('rest.end'));
        $this->post(route('rest.start'));

        // テスト手順3: 「休憩戻」ボタンが表示されることを確認する
        // 期待挙動: 画面上に「休憩戻」ボタンが表示される
        $this->get('/attendance')
            ->assertSee('休憩戻');
    }

    /**
     * @test
     * 休憩時刻が勤怠一覧画面で確認できる
     */
    public function rest_time_is_displayed_on_attendance_list()
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        // テスト手順1: ステータスが勤務中のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 1,
        ]);

        Work::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_start' => '09:00:00',
        ]);

        $this->actingAs($user);

        // テスト手順2: 休憩入と休憩戻の処理を行う
        Carbon::setTestNow('2026-01-01 12:00:00');
        $this->post(route('rest.start'));
        Carbon::setTestNow('2026-01-01 12:30:00');
        $this->post(route('rest.end'));

        // テスト手順3: 勤怠一覧画面から休憩の日付を確認する
        // 期待挙動: 勤怠一覧画面に休憩時刻が正確に記録されている
        $this->get(route('attendance.list'))
            ->assertStatus(200)
            ->assertSee('0:30');
    }
}
