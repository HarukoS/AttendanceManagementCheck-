<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 勤務外の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_displayed_correctly_when_off_duty()
    {
        // テスト手順1:ステータスが勤務外のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 0,
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順2:勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // テスト手順3:画面に表示されているステータスを確認する
        $response->assertStatus(200);

        // 期待挙動：画面上に表示されているステータスが「勤務外」となる
        $response->assertSee('勤務外');
    }

    /**
     * @test
     * 出勤中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_displayed_correctly_when_working()
    {
        // テスト手順1:ステータスが出勤中のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 1,
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順2:勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // テスト手順3: 画面に表示されているステータスを確認する
        $response->assertStatus(200);

        // 期待挙動：画面上に表示されているステータスが「出勤中」となる
        $response->assertSee('出勤中');
    }

    /**
     * @test
     * 休憩中の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_displayed_correctly_when_on_break()
    {
        // テスト手順1:ステータスが休憩中のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 2,
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順2:勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // テスト手順3:画面に表示されているステータスを確認する
        $response->assertStatus(200);

        // 期待挙動:画面上に表示されているステータスが「休憩中」となる
        $response->assertSee('休憩中');
    }

    /**
     * @test
     * 退勤済の場合、勤怠ステータスが正しく表示される
     */
    public function test_status_is_displayed_correctly_when_finished()
    {
        // テスト手順1:ステータスが退勤済のユーザーにログインする
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 3,
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順2:勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // テスト手順3:画面に表示されているステータスを確認する
        $response->assertStatus(200);

        // 期待挙動:画面上に表示されているステータスが「退勤済」となる
        $response->assertSee('退勤済');
    }
}
