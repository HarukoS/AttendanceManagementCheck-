<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function work_start_after_work_end_shows_error()
    {
        $user = User::factory()->create(['role' => 'user']);

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1:勤怠情報が登録されたユーザーにログインをする
        /** @var \App\Models\User $user */
        $this->actingAs($user);

        // テスト手順2: 勤怠詳細ページを開く
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細');

        // テスト手順3: 出勤時間を退勤時間より後に設定する
        // テスト手順4: 保存処理をする
        $this->post(route('attendance.request.store', $work->id), [
            'work_start' => '19:00',
            'work_end'   => '18:00',
            'reason'     => 'テスト修正',
        ])
            ->assertSessionHasErrors(['work_start']);

        // 期待挙動: 「出勤時間が不適切な値です」というバリデーションメッセージが表示される
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function rest_start_after_work_end_shows_error()
    {
        $user = User::factory()->create(['role' => 'user']);

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1:勤怠情報が登録されたユーザーにログインをする
        /** @var \App\Models\User $user */
        $this->actingAs($user);

        // テスト手順2: 勤怠詳細ページを開く
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細');

        // テスト手順3: 休憩開始時間を退勤時間より後に設定する
        // テスト手順4: 保存処理をする
        $this->post(route('attendance.request.store', $work->id), [
            'work_start' => '09:00',
            'work_end'   => '18:00',
            'rests' => [
                'new' => [
                    'rest_start' => '19:00',
                    'rest_end'   => '19:30',
                ],
            ],
            'reason' => 'テスト修正',
        ])
            ->assertSessionHasErrors();

        // 期待挙動: 「休憩時間が不適切な値です」というバリデーションメッセージが表示される
        $this->get(route('attendance.detail', $work->id))
            ->assertSee('休憩時間が不適切な値です');
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function rest_end_after_work_end_shows_error()
    {
        $user = User::factory()->create(['role' => 'user']);

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1:勤怠情報が登録されたユーザーにログインをする
        /** @var \App\Models\User $user */
        $this->actingAs($user);

        // テスト手順2: 勤怠詳細ページを開く
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細');

        // テスト手順3: 休憩終了時間を退勤時間より後に設定する
        // テスト手順4: 保存処理をする
        $this->post(route('attendance.request.store', $work->id), [
            'work_start' => '09:00',
            'work_end'   => '18:00',
            'rests' => [
                'new' => [
                    'rest_start' => '17:30',
                    'rest_end'   => '19:00',
                ],
            ],
            'reason' => 'テスト修正',
        ])
            ->assertSessionHasErrors();

        // 期待挙動: 「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
        $this->get(route('attendance.detail', $work->id))
            ->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function empty_reason_shows_error()
    {
        $user = User::factory()->create(['role' => 'user']);

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1:勤怠情報が登録されたユーザーにログインをする
        /** @var \App\Models\User $user */
        $this->actingAs($user);

        // テスト手順2: 勤怠詳細ページを開く
        $this->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細');

        // テスト手順3: 備考欄を未入力のまま保存処理をする
        $this->post(route('attendance.request.store', $work->id), [
            'work_start' => '09:00',
            'work_end'   => '18:00',
            'reason'     => '',
        ])
            ->assertSessionHasErrors(['reason']);

        // 期待挙動: 「備考を記入してください」というバリデーションメッセージが表示される
        $this->get(route('attendance.detail', $work->id))
            ->assertSee('備考を記入してください');
    }
}
