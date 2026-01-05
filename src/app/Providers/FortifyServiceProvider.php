<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LogoutResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\LoginResponse;

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

        // 管理者／ユーザー判定付き認証
        Fortify::authenticateUsing(function (Request $request) {

            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            // 管理者ログイン画面から来た場合は role=admin のみ許可
            if ($request->routeIs('admin.login.submit') && $user->role !== 'admin') {
                return null;
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
