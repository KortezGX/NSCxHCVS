@extends('app')

@section('title', '新增出版社管理員')

@section('content')
    <h1>新增出版社管理員</h1>
    <form action="{{ route('managers.store') }}" method="POST">
        @csrf
        <div class="form-group mb-3">
            <label for="publisher_id">所屬出版社</label>
            <select class="form-control" id="publisher_id" name="publisher_id" required>
                @foreach ($publishers as $publisher)
                    <option value="{{ $publisher->id }}">{{ $publisher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group mb-3">
            <label for="account">帳號</label>
            <input type="text" class="form-control" id="account" name="account" required>
        </div>
        <div class="form-group mb-3">
            <label for="password">密碼</label>
            <input type="text" class="form-control" id="password" name="password" required>
        </div>
        <div class="form-group mb-3">
            <label for="name">姓名</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <button type="submit" class="btn btn-primary">新增出版社管理員</button>
    </form>
@endsection
