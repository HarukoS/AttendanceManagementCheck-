<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる
     */
    public function admin_can_view_all_users_name_and_email()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $users = User::factory()->count(3)->create();

        // テスト手順1: 管理者でログインする
        // テスト手順2: スタッフ一覧ページを開く
        /** @var \App\Models\User $admin */
        $response = $this->actingAs($admin)
            ->get(route('admin.staff.list'));

        $response->assertStatus(200);

        // 期待挙動: 全ての一般ユーザーの氏名とメールアドレスが正しく表示されている
        foreach ($users as $user) {
            $response
                ->assertSee($user->name)
                ->assertSee($user->email);
        }
    }

    /**
     * @test
     * ユーザーの勤怠情報が正しく表示される
     */
    public function admin_can_view_selected_user_attendance_list()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1: 管理者でログインする
        // テスト手順2: 選択したユーザーの勤怠一覧ページを開く
        // 期待挙動: 勤怠情報が正確に表示される
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.staff', $user->id))
            ->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /**
     * @test
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function previous_month_attendance_is_displayed_on_admin_staff_attendance()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        Work::create([
            'user_id'    => $user->id,
            'date'       => '2025-12-10',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1: 管理者ユーザーにログインする
        // テスト手順2: 勤怠一覧ページを開く
        // テスト手順3: 「前月」ボタンを押す
        // 期待挙動: 前月の情報が表示されている
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.staff', [
                'id'    => $user->id,
                'month' => '2025-12',
            ]))
            ->assertStatus(200)
            ->assertSee('2025/12')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    /**
     * @test
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     */
    public function next_month_attendance_is_displayed_on_admin_staff_attendance()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-02-10',
            'work_start' => '10:00:00',
            'work_end'   => '19:00:00',
        ]);

        // テスト手順1: 管理者ユーザーにログインする
        // テスト手順2: 勤怠一覧ページを開く
        // テスト手順3: 「翌月」ボタンを押す
        // 期待挙動: 翌月の情報が表示されている
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.staff', [
                'id'    => $user->id,
                'month' => '2026-02',
            ]))
            ->assertStatus(200)
            ->assertSee('2026/02')
            ->assertSee('10:00')
            ->assertSee('19:00');
    }

    /**
     * @test
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function admin_can_navigate_to_attendance_detail_from_staff_attendance()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user  = User::factory()->create();

        $work = Work::create([
            'user_id'    => $user->id,
            'date'       => '2026-01-15',
            'work_start' => '09:00:00',
            'work_end'   => '18:00:00',
        ]);

        // テスト手順1: 管理者ユーザーにログインする
        // テスト手順2: 勤怠一覧ページを開く
        // テスト手順3: 「詳細」ボタンを押す
        // 期待挙動: その日の勤怠詳細画面に遷移する
        /** @var \App\Models\User $admin */
        $this->actingAs($admin)
            ->get(route('admin.attendance.detail', $work->id))
            ->assertStatus(200)
            ->assertSee('勤怠詳細')
            ->assertSee($user->name);
    }
}
