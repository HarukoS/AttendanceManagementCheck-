<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 会員登録後、認証メールが送信される
     */
    public function verification_email_is_sent_after_registration()
    {
        Notification::fake();

        // テスト手順1: 会員登録をする
        $user = User::factory()->unverified()->create();

        // テスト手順2: 認証メールを送信する
        $user->sendEmailVerificationNotification();

        // 期待挙動：登録したメールアドレス宛に認証メールが送信されている
        Notification::assertSentTo($user, \App\Notifications\CustomVerifyEmail::class);
    }

    /**
     * @test
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとMailHogページが開く
     */
    public function verify_email_page_has_mailhog_link()
    {
        $user = User::factory()->unverified()->create();

        // テスト手順1: メール認証導線画面を表示する
        // テスト手順2:「認証はこちらから」ボタンを押下
        // テスト手順3: MailHog画面が開く
        // 期待挙動：MailHog画面が開く
        $this->actingAs($user)
            ->get('/email/verify')
            ->assertStatus(200)
            ->assertSee('認証はこちらから')
            ->assertSee('http://localhost:8025');
    }

    /**
     * @test
     * メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
     */
    public function user_can_verify_email_and_redirect_to_attendance_page()
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id'   => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // テスト手順1: メール認証を完了する
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // テスト手順2: 勤怠登録画面を表示する
        // 期待挙動: 勤怠登録画面に遷移する
        $this->assertStringContainsString(
            route('attendance', [], false),
            $response->headers->get('Location')
        );
    }
}
