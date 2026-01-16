@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_login.css') }}">
@endsection

@php
$hideHeaderSearch = true;
$hideHeaderNav = true;
@endphp

@section('content')
<div class="login-form__content">
    <div class="login-form__heading">
        <h2>管理者ログイン</h2>
    </div>
    <form class="form" action="/login" method="post">
        @csrf
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">メールアドレス</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="email" name="email" value="{{ old('email') }}" />
                </div>
                <div class="form__error">
                    @if ($errors->login->has('email'))
                    {{ $errors->login->first('email') }}
                    @endif
                </div>
            </div>
        </div>
        <div class="form__group">
            <div class="form__group-title">
                <span class="form__label--item">パスワード</span>
            </div>
            <div class="form__group-content">
                <div class="form__input--text">
                    <input type="password" name="password" />
                </div>
                <div class="form__error">
                    @if ($errors->login->has('password'))
                    {{ $errors->login->first('password') }}
                    @endif
                </div>
            </div>
        </div>
        <div class="form__button">
            <button class="form__button-submit" type="submit">管理者ログインする</button>
        </div>
    </form>
</div>
@endsection