@extends('app')

@section('title', '登入')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="{{ route('login.submit') }}" method="POST" class="mt-5">
                @csrf
                <h2 class="text-center mb-4">後台管理登入</h2>
                <div class="mb-3">
                    <label for="account" class="form-label">帳號：</label>
                    <input type="text" name="account" id="account" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">密碼：</label>
                    <input type="password" name="password" id="password" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary w-100">登入</button>
            </form>
        </div>
    </div>
@endsection
