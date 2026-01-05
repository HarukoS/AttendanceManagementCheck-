<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header-utilities">
                <a class="header__logo" href="/">
                    <img src="{{ asset('img/logo.svg') }}" alt="logo">
                </a>

                <nav>
                    <ul class="header-nav">
                        @if (Auth::check())
                        @if (auth()->user()->role === 'user')
                        <li class="header-nav__item"><a href="/attendance">勤怠</a></li>
                        <li class="header-nav__item"><a href="/attendance/list">勤怠一覧</a></li>
                        <li class="header-nav__item"><a href="/stamp_correction_request/list">申請</a></li>
                        @elseif (auth()->user()->role === 'admin')
                        <li class="header-nav__item"><a href="/admin/attendance/list">勤怠一覧</a></li>
                        <li class="header-nav__item"><a href="/admin/staff/list">スタッフ一覧</a></li>
                        <li class="header-nav__item"><a href="/stamp_correction_request/list">申請一覧</a></li>
                        @endif
                        <li class="header-nav__item">
                            <form class="form" action="/logout" method="post">
                                @csrf
                                <button class="header-nav__button">ログアウト</button>
                            </form>
                        </li>
                        @endif
                    </ul>
                </nav>

            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>