<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceDateTimeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_datetime_is_displayed_correctly_on_attendance_screen()
    {
        $fixedNow = Carbon::create(2026, 1, 13, 10, 30);
        Carbon::setTestNow($fixedNow);

        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'status' => 0,
            'role' => 'user',
        ]);

        $this->actingAs($user);

        // テスト手順1: 勤怠打刻画面を開く
        $response = $this->get('/attendance');

        // テスト手順2: 画面に表示されている日時情報を確認する
        $response->assertStatus(200);

        // 期待挙動：画面に表示されている日時が現在の日時と一致する
        $response->assertSee(
            $fixedNow->locale('ja')->isoFormat('YYYY年M月D日(ddd)')
        );
        $response->assertSee(
            $fixedNow->format('H:i')
        );
    }
}
