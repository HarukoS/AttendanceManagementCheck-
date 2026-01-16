<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use App\Models\Request as CorrectionRequest;
use App\Models\RequestDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 承認待ちの修正申請が全て表示されている
     */
    public function admin_can_view_all_pending_correction_requests()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        $request = CorrectionRequest::create([
            'user_id'      => $user->id,
            'work_id'      => $work->id,
            'reason'       => '修正申請',
            'status'       => 0,
            'requested_at' => now(),
        ]);

        // テスト手順1: 管理者ユーザーにログインする
        // テスト手順2: 修正申請一覧ページを開き、承認待ちのタブを開く
        // 期待挙動: 全ユーザーの未承認の修正申請が表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('stamp.correction.user.list', ['status' => 0]))
            ->assertStatus(200)
            ->assertSee('承認待ち')
            ->assertSee($user->name)
            ->assertSee('修正申請');
    }

    /**
     * @test
     * 承認済みの修正申請が全て表示されている
     */
    public function admin_can_view_all_approved_correction_requests()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        CorrectionRequest::create([
            'user_id'      => $user->id,
            'work_id'      => $work->id,
            'reason'       => '承認済み申請',
            'status'       => 1,
            'requested_at' => now()->subDay(),
            'approved_at'  => now(),
        ]);

        // テスト手順1: 管理者ユーザーにログインする
        // テスト手順2: 修正申請一覧ページを開き、承認済みのタブを開く
        // 期待挙動: 全ユーザーの承認済みの修正申請が表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('stamp.correction.user.list', ['status' => 1]))
            ->assertStatus(200)
            ->assertSee('承認済み')
            ->assertSee($user->name)
            ->assertSee('承認済み申請');
    }

    /**
     * @test
     * 修正申請の詳細内容が正しく表示されている
     */
    public function admin_can_view_correction_request_detail()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        $request = CorrectionRequest::create([
            'user_id'      => $user->id,
            'work_id'      => $work->id,
            'reason'       => '開始時刻修正',
            'status'       => 0,
            'requested_at' => now(),
        ]);

        RequestDetail::create([
            'request_id' => $request->id,
            'type'       => 'work_start',
            'new_time'   => '10:00:00',
        ]);

        // テスト手順1: 管理者ユーザーにログインする
        // テスト手順2: 修正申請の詳細画面を開く
        // 期待挙動: 申請内容が正しく表示されている
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.request.approve.show', $request->id))
            ->assertStatus(200)
            ->assertSee('10:00');
    }

    /**
     * @test
     * 修正申請の承認処理が正しく行われる
     */
    public function admin_can_approve_correction_request_and_update_work()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        $request = CorrectionRequest::create([
            'user_id'      => $user->id,
            'work_id'      => $work->id,
            'reason'       => '開始時刻修正',
            'status'       => 0,
            'requested_at' => now(),
        ]);

        RequestDetail::create([
            'request_id' => $request->id,
            'type'                  => 'work_start',
            'new_time'              => '10:00:00',
        ]);

        // テスト手順1: 管理者ユーザーにログインする
        // テスト手順2: 修正申請の詳細画面で「承認」ボタンを押す
        // 期待挙動: 修正申請が承認され、勤怠情報が更新される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->post(route('admin.request.approve', $request->id))
            ->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'id'     => $request->id,
            'status' => 1,
        ]);
    }
}
