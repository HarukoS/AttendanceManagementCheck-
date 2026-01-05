@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_list.css') }}">
@endsection

@section('content')
<div class="container">

    <h1 class="page-title">スタッフ一覧</h1>

    <div class="attendance-card">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @php
                        $hasWork = \App\Models\Work::where('user_id', $user->id)->exists();
                        @endphp

                        @if ($hasWork)
                        <a href="{{ route('admin.attendance.staff', $user->id) }}" class="detail-link">詳細</a>
                        @else
                        <span class="detail-link disabled">詳細</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection