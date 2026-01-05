@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_staff.css') }}">
@endsection

@section('content')
<div class="container">

    <h1 class="page-title">{{ $staff->name }}さんの勤怠</h1>

    <!-- 月移動ナビ -->
    <div class="month-nav">
        <a href="{{ route('admin.attendance.staff', ['id' => $staff->id, 'month' => $prevMonth]) }}">← 前月</a>

        <strong><img class="calendar__img" src="{{ asset('img/calendar.png') }}" alt="カレンダー" />{{ $targetMonth->format('Y年m月') }}</strong>

        <a href="{{ route('admin.attendance.staff', ['id' => $staff->id, 'month' => $nextMonth]) }}">翌月 →</a>
    </div>

    <!-- 勤怠一覧カード -->
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
                    <td>{{ $day['date']->format('m/d(D)') }}</td>
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
                        @if ($work && $work->work_end)
                        {{ floor($work->getRestMinutes() / 60) }}:
                        {{ str_pad($work->getRestMinutes() % 60, 2, '0', STR_PAD_LEFT) }}
                        @else
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