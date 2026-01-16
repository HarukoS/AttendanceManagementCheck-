@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="container">

    <h1 class="page-title">勤怠一覧</h1>

    <div class="month-nav">
        <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="month-nav__change">
            <img class="arrow_left__img" src="{{ asset('img/arrow.png') }}" alt="arrow" />前月
        </a>
        <strong><img class="calendar__img" src="{{ asset('img/calendar.png') }}" alt="カレンダー" />{{ $targetMonth->format('Y/m') }}</strong>
        <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="month-nav__change">
            翌月<img class="arrow_right__img" src="{{ asset('img/arrow.png') }}" alt="arrow" />
        </a>
    </div>

    <div class="attendance-card">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($days as $day)
                @php
                $work = $day['work'];
                @endphp
                <tr>
                    <td>{{ $day['date']->locale('ja')->isoFormat('MM/DD(ddd)') }}</td>
                    <td>
                        {{ $work?->work_start
            ? \Carbon\Carbon::parse($work->work_start)->format('H:i')
            : ' ' }}
                    </td>
                    <td>
                        {{ $work?->work_end
            ? \Carbon\Carbon::parse($work->work_end)->format('H:i')
            : ' ' }}
                    </td>
                    <td>
                        @if ($work && $work->rests->whereNotNull('rest_end')->count() > 0)
                        {{ floor($work->getRestMinutes() / 60) }}:{{ str_pad($work->getRestMinutes() % 60, 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </td>
                    <td>
                        @if ($work && $work->work_end)
                        {{ floor($work->getActualMinutes() / 60) }}:
                        {{ str_pad($work->getActualMinutes() % 60, 2, '0', STR_PAD_LEFT) }}
                        @else
                        @endif
                    </td>
                    <td>
                        @if ($work)
                        <a href="{{ route('attendance.detail', $work->id) }}" class="detail-link">詳細</a>
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