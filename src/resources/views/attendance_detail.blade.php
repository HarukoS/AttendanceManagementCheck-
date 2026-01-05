@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="container {{ $display['is_pending'] ? 'is-pending' : '' }}">

    <h1 class="page-title">勤怠詳細</h1>

    <form method="POST" action="{{ route('attendance.request.store', $work->id) }}">
        @csrf

        <div class="detail-card">
            <table class="detail-table">

                <tr>
                    <th>名前</th>
                    <td>{{ $user->name }}</td>
                </tr>

                <tr>
                    <th>日付</th>
                    <td class="date-row">
                        <span>{{ $work->date->format('Y年') }}</span>
                        <span>{{ $work->date->format('n月j日') }}</span>
                    </td>
                </tr>

                <tr>
                    <th>出勤・退勤</th>
                    <td class="time-row">
                        <input type="text"
                            name="work_start"
                            class="time-input"
                            value="{{ old('work_start', optional($display['work_start'])->format('H:i')) }}">

                        <span class="tilde">〜</span>

                        <input type="text"
                            name="work_end"
                            class="time-input"
                            value="{{ old('work_end', optional($display['work_end'])->format('H:i')) }}">
                    </td>
                </tr>

                {{-- 既存の休憩 --}}
                @foreach ($display['rests'] as $index => $rest)
                <tr>
                    <th>休憩{{ $index + 1 }}</th>
                    <td class="time-row">
                        <input type="text"
                            name="rests[{{ $rest->id }}][rest_start]"
                            class="time-input"
                            value="{{ old("rests.$rest->id.rest_start", optional($rest->rest_start)->format('H:i')) }}">

                        <span class="tilde">〜</span>

                        <input type="text"
                            name="rests[{{ $rest->id }}][rest_end]"
                            class="time-input"
                            value="{{ old("rests.$rest->id.rest_end", optional($rest->rest_end)->format('H:i')) }}">
                    </td>
                </tr>
                @endforeach

                {{-- 追加休憩 --}}
                @if (!$display['is_pending'])
                <tr>
                    <th>休憩{{ count($display['rests']) + 1 }}</th>
                    <td class="time-row">
                        <input type="text"
                            name="rests[new][rest_start]"
                            class="time-input"
                            placeholder="--:--">

                        <span class="tilde">〜</span>

                        <input type="text"
                            name="rests[new][rest_end]"
                            class="time-input"
                            placeholder="--:--">
                    </td>
                </tr>
                @endif

                {{-- 備考 --}}
                <tr>
                    <th>備考</th>
                    <td>
                        <textarea
                            name="reason"
                            class="reason-input">{{ old('reason', $display['reason']) }}</textarea>
                    </td>
                </tr>

            </table>
        </div>

        <div class="detail-button-area">
            @if ($display['is_pending'])
            <div class="request-warning">
                ※承認待ちのため修正はできません。
            </div>
            @else
            <button type="submit" class="edit-button">
                修正
            </button>
            @endif
        </div>

    </form>

    @if (count($errors) > 0)
    <ul class="error-text">
        @foreach ($errors->all() as $error)
        <li>{{$error}}</li>
        @endforeach
    </ul>
    @endif

</div>
@endsection