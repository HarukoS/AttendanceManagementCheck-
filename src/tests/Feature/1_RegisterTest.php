<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 名前が未入力の場合、バリデーションメッセージが表示される
     */
    public function test_name_is_required()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        // テスト手順1: 名前以外のユーザー情報を入力する
        $formData = [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // テスト手順2: 会員登録の処理を行う
        $response = $this->post('/register', $formData);

        // 期待挙動:「お名前を入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * @test
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        // テスト手順1: メールアドレス以外のユーザー情報を入力する
        $formData = [
            'name' => 'test',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // テスト手順2: 会員登録の処理を行う
        $response = $this->post('/register', $formData);

        // 期待挙動:「メールアドレスを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが8文字未満の場合、バリデーションメッセージが表示される
     */
    public function test_password_must_be_at_least_8_characters()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        // テスト手順1: パスワードを8文字未満にし、ユーザー情報を入力する
        $formData = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'passwor',
            'password_confirmation' => 'passwor',
        ];

        // テスト手順2: 会員登録の処理を行う
        $response = $this->post('/register', $formData);

        // 期待挙動:「パスワードは8文字以上で入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * @test
     * パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_must_match()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        // テスト手順1: 確認用のパスワードとパスワードを一致させず、ユーザー情報を入力する
        $formData = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ];

        // テスト手順2: 会員登録の処理を行う
        $response = $this->post('/register', $formData);

        // 期待挙動:「パスワードと一致しません」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません',
        ]);
    }

    /**
     * @test
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        // テスト手順1: パスワード以外のユーザー情報を入力する
        $formData = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ];

        // テスト手順2: 会員登録の処理を行う
        $response = $this->post('/register', $formData);

        // 期待挙動:「パスワードを入力してください」というバリデーションメッセージが表示される
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * @test
     * フォームに内容が入力されていた場合、データが正常に保存される
     */
    public function test_user_is_registered()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        // テスト手順1: ユーザー情報を入力する
        $formData = [
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // テスト手順2: 会員登録の処理を行う
        $response = $this->post('/register', $formData);

        // 期待挙動：データベースに登録したユーザー情報が保存される
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }
}
