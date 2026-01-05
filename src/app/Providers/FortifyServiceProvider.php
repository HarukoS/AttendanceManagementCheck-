<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\LoginRequest;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;
use Illuminate\Validation\ValidationException;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 新規ユーザー作成
        Fortify::createUsersUsing(CreateNewUser::class);

        // 登録画面
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // ログイン画面
        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        // 管理者／ユーザー判定付き認証
        Fortify::authenticateUsing(function (Request $request) {

            $loginRequest = LoginRequest::createFrom($request);

            // ✅ 日本語バリデーション
            Validator::make(
                $loginRequest->all(),
                $loginRequest->rules(),
                $loginRequest->messages()
            )->validate();

            // 認証失敗・メール未認証・レート制限はここで例外
            $loginRequest->authenticate();

            // ここから追加条件
            $user = auth()->user();

            // 管理者ログイン画面制御
            if ($request->routeIs('admin.login.submit') && $user->role !== 'admin') {
                Auth::logout();

                throw ValidationException::withMessages([
                    'email' => '管理者アカウントではありません。',
                ]);
            }

            return $user;
        });

        // ログイン後遷移先の分岐
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    $user = auth()->user();

                    if ($user->role === 'admin') {
                        return redirect()->route('admin.attendance.list');
                    }

                    return redirect()->route('attendance');
                }
            };
        });

        // ログイン試行制限
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            return Limit::perMinute(10)->by($email . $request->ip());
        });

        // ログアウト後のリダイレクト
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {
                return redirect('/login'); // ログアウト後にログインページへ
            }
        });
    }
}
