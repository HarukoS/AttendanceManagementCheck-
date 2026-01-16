<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 修正申請処理が実行される
     */
    public function correction_request_is_created_and_visible()
    {
        $user  = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1:勤怠情報が登録されたユーザーにログインをする
        // テスト手順2:勤怠詳細を修正し保存処理をする
        /** @var \App\Models\User $user */
        $this->actingAs($user)
            ->post(route('attendance.request.store', $work->id), [
                'work_start' => '09:30',
                'work_end'   => '18:00',
                'reason'     => '修正申請テスト',
            ])
            ->assertRedirect();

        // DB確認
        $this->assertDatabaseHas('requests', [
            'user_id' => $user->id,
            'work_id' => $work->id,
            'status'  => 0,
        ]);

        // テスト手順3: 管理者ユーザーで承認画面と申請一覧画面を確認する
        // 期待挙動: 修正申請が実行され、管理者の承認画面と申請一覧画面に表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('stamp.correction.user.list', ['status' => 0]))
            ->assertStatus(200)
            ->assertSee('修正申請テスト');

        $this->actingAs($admin)
            ->get(route('stamp.correction.user.list', ['status' => 0]))
            ->assertStatus(200)
            ->assertSee($user->name);
    }

    /**
     * @test
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function pending_list_shows_only_my_requests()
    {
        Carbon::setTestNow('2026-01-01 10:00:00');

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $work = Work::create([
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
        ]);

        // テスト手順1:勤怠情報が登録されたユーザーにログインをする
        $this->actingAs($user);

        // テスト手順2:勤怠詳細を修正し保存処理をする
        $this->post(route('attendance.request.store', $work->id), [
            'work_start' => '09:30',
            'work_end'   => '18:00',
            'reason'     => '修正申請テスト',
        ])->assertRedirect();

        // テスト手順3:申請一覧画面を確認する
        // 期待挙動: 申請一覧に自分の申請が全て表示されている
        $this->get(route('stamp.correction.user.list', ['status' => 0]))
            ->assertStatus(200)
            ->assertSee('修正申請テスト')
            ->assertSee($user->name);
    }

    /**
     * @test
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function approved_requests_are_displayed_after_correction_flow()
    {
        $user  = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $work = Work::create([
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
        ]);

        // テスト手順1:勤怠情報が登録されたユーザーにログインをする
        // テスト手順2:勤怠詳細を修正し保存処理をする
        /** @var \App\Models\User $user */
        $this->actingAs($user)
            ->post(route('attendance.request.store', $work->id), [
                'work_start' => '10:00',
                'work_end'   => '19:00',
                'reason'     => '時間修正テスト',
            ])
            ->assertRedirect();

        $request = \App\Models\Request::first();

        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->post(route('admin.request.approve', $request->id))
            ->assertRedirect();

        // テスト手順3:申請一覧画面を確認する
        // テスト手順4:管理者が承認した修正申請が全て表示されていることを確認
        // 期待挙動: 承認済みに管理者が承認した申請が全て表示されている
        $this->actingAs($user)
            ->get(route('stamp.correction.user.list', ['status' => 1]))
            ->assertStatus(200)
            ->assertSee('時間修正テスト');
    }

    /**
     * @test
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function request_detail_link_redirects_to_attendance_detail()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['role' => 'user']);

        $work = Work::create([
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
        ]);

        // テスト手順1:勤怠情報が登録されたユーザーにログインをする
        // テスト手順2:勤怠詳細を修正し保存処理をする
        $this->actingAs($user)->post(
            route('attendance.request.store', $work),
            [
                'work_start' => '09:00',
                'work_end'   => '18:00',
                'reason'     => 'テスト修正',
            ]
        )->assertRedirect();

        // テスト手順3:申請一覧画面を開く
        $response = $this->actingAs($user)
            ->get(route('stamp.correction.user.list', ['status' => 0]))
            ->assertStatus(200);

        // テスト手順4:「詳細」ボタンを押す
        // 期待挙動: 勤怠詳細画面に遷移する
        $this->actingAs($user)
            ->get(route('attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細');
    }
}
