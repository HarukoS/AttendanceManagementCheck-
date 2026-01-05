@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}">
@endsection

@section('content')
<div class="container">

    <h1 class="page-title">{{$today->format('Y年m月d日')}}の勤怠</h1>

    <div class="date-nav">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDay]) }}" class="date-nav__change">
            <img class="arrow_left__img" src="{{ asset('img/arrow.png') }}" alt="arrow" />前日
        </a>

        <strong><img class="calendar__img" src="{{ asset('img/calendar.png') }}" alt="カレンダー" />{{$today->format('Y/m/d')}}</strong>

        <a href="{{ route('admin.attendance.list', ['date' => $nextDay]) }}" class="date-nav__change">
            翌日<img class="arrow_right__img" src="{{ asset('img/arrow.png') }}" alt="arrow" />
        </a>
    </div>

    <div class="attendance-card">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($works as $work)
                <tr>
                    <td>{{ $work->user->name }}</td>
                    <td>{{ optional($work->work_start)->format('H:i') }}</td>
                    <td>{{ optional($work->work_end)->format('H:i') }}</td>
                    <td>{{ $work->rest_time }}</td>
                    <td>{{ $work->actual_time }}</td>
                    <td>
                        @if ($work)
                        <a href="{{ route('admin.attendance.detail', $work->id) }}" class="detail-link">詳細</a>
                        @else
                        詳細
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection