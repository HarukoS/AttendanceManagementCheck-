@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="container">
    @if($user->status == 0)
    <div class="tag">勤務外</div>
    @elseif($user->status == 1)
    <div class="tag">出勤中</div>
    @elseif($user->status == 2)
    <div class="tag">休憩中</div>
    @elseif($user->status == 3)
    <div class="tag">退勤済</div>
    @endif

    <div class="date">
        {{ $now->locale('ja')->isoFormat('YYYY年M月D日(ddd)') }}
    </div>

    <div class="time">{{ $now->format('H:i') }}</div>

    @if($user->status == 0)
    <form action="{{ route('work.start') }}" method="POST">
        @csrf
        <button class="btn">出勤</button>
    </form>
    @elseif($user->status == 1)
    <div class="buttons">
        <form action="{{ route('work.end') }}" method="POST">
            @csrf
            <button class="btn">退勤</button>
        </form>
        <form action="{{ route('rest.start') }}" method="POST">
            @csrf
            <button class="btn-white">休憩入</button>
        </form>
    </div>
    @elseif($user->status == 2)
    <form action="{{ route('rest.end') }}" method="POST">
        @csrf
        <button class="btn-white">休憩戻</button>
    </form>
    @elseif($user->status == 3)
    <div class="end">お疲れ様でした。</div>
    @endif
</div>
@endsection