@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@php
$hideHeaderSearch = true;
$hideHeaderNav = true;
@endphp

@section('content')
<div class="verify-email__content">
    <p class="verify-email__text">
        {{ __('登録していただいたメールアドレスに認証メールを送付しました。') }}
    </p>
    <p class="verify-email__text">
        {{ __('メール認証を完了してください。') }}
    </p>
    <form class="form__item" method="GET" action="http://localhost:8025" target="_blank">
        @csrf
        <button type="submit" class="form__button">
            {{ __('認証はこちらから') }}
        </button>
    </form>
    <form class="form__item" method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="form__send-button">
            {{ __('認証メールを再送する') }}
        </button>
    </form>
</div>
@endsection