<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_email_is_required_on_login()
    {
        // テスト手順1: 管理者ユーザーを登録する
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // テスト手順2: メールアドレス以外のユーザー情報を入力する
        // テスト手順3: ログインの処理を行う
        $response = $this->withHeaders([
            'Accept' => 'text/html',
        ])->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        // 期待挙動：「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrorsIn('login', [
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_admin_password_is_required_on_login()
    {
        // テスト手順1: 管理者ユーザーを登録する
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // テスト手順2: パスワード以外のユーザー情報を入力する
        // テスト手順3: ログインの処理を行う
        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        // 期待挙動：「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrorsIn('login', [
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function test_admin_login_fails_with_invalid_credentials()
    {
        $this->flushSession();

        // テスト手順1: 管理者ユーザーを登録する
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // テスト手順2: 誤ったメールアドレスのユーザー情報を入力する
        // テスト手順3: ログインの処理を行う
        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong-admin@example.com',
            'password' => 'password123',
        ]);

        // 期待挙動：「ログイン情報が登録されていません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrorsIn('login', [
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
