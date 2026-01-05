@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/approve.css') }}">
@endsection

@section('content')
<div class="container">
    <h1 class="page-title">勤怠詳細</h1>

    <div class="detail-card">
        <table class="detail-table">
            <tr>
                <th>名前</th>
                <td>{{ $request->user->name }}</td>
            </tr>

            <tr>
                <th>日付</th>
                <td class="date-row">
                    <span>{{ optional($request->work->date)->format('Y年') }}</span>
                    <span>{{ optional($request->work->date)->format('n月j日') }}</span>
                </td>
            </tr>

            <tr>
                <th>出勤・退勤</th>
                <td class="time-row">
                    {{ optional($display['work_start'])->format('H:i') ?? '-' }}
                    <span class="tilde">〜</span>
                    {{ optional($display['work_end'])->format('H:i') ?? '-' }}
                </td>
            </tr>

            @foreach($display['rests'] as $index => $rest)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td class="time-row">
                    {{ optional($rest->rest_start)->format('H:i') ?? '-' }}
                    <span class="tilde">〜</span>
                    {{ optional($rest->rest_end)->format('H:i') ?? '-' }}
                </td>
            </tr>
            @endforeach

            <tr>
                <th>備考</th>
                <td>{{ $display['reason'] ?? '-' }}</td>
            </tr>

        </table>
    </div>

    <div class="detail-button-area">
        @if($request->status === 0)
        <form method="POST" action="{{ route('admin.request.approve', $request->id) }}">
            @csrf
            <button type="submit" class="approve-button">
                承認
            </button>
        </form>
        @else
        <div class="approved-button">
            承認済み
        </div>
        @endif
    </div>
</div>
@endsection