@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/stamp_correction_request.css') }}">
@endsection

@section('content')
<div class="container">

    <h1 class="page-title">申請一覧</h1>

    <div class="tab">
        <a href="{{ route('stamp.correction.user.list', ['status' => 0]) }}"
            class="tab__pending {{ $status == 0 ? 'active' : '' }}">
            承認待ち
        </a>

        <a href="{{ route('stamp.correction.user.list', ['status' => 1]) }}"
            class="tab__approved {{ $status == 1 ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    <div class="request-card">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $request)
                <tr>
                    <td>
                        {{ $request->status === 0 ? '承認待ち' : '承認済み' }}
                    </td>

                    <td>{{ $request->user->name }}</td>

                    <td>
                        {{ optional($request->work->date)->format('Y/m/d') }}
                    </td>

                    <td>{{ $request->reason }}</td>

                    <td>
                        {{ $request->requested_at->format('Y/m/d') }}
                    </td>

                    <td>
                        @if(auth()->user()->role === 'admin')
                        <!-- Adminはapproveページへ -->
                        <a href="{{ route('admin.request.approve.show', $request->id) }}" class="detail-link">
                            詳細
                        </a>
                        @else
                        <!-- 一般ユーザーは元のattendance.detailへ -->
                        <a href="{{ route('attendance.detail', $request->work_id) }}" class="detail-link">
                            詳細
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection