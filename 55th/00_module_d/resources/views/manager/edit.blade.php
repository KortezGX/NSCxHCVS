@extends('app')

@section('title', '編輯出版社管理員')

@section('content')
    <h1>編輯出版社管理員</h1>
    <form action="{{ route('managers.update', $manager) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="publisher_id">所屬出版社</label>
            <select class="form-control" id="publisher_id" name="publisher_id" required>
                @foreach ($publishers as $publisher)
                    <option value="{{ $publisher->id }}" {{ $manager->publisher_id == $publisher->id ? 'selected' : '' }}>
                        {{ $publisher->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="account">帳號</label>
            <input type="text" class="form-control" id="account" name="account" value="{{ $manager->account }}" required>
        </div>
        <div class="form-group">
            <label for="password">密碼</label>
            <input type="text" class="form-control" id="password" name="password" value="" required>
        </div>
        <div class="form-group">
            <label for="name">姓名</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $manager->name }}" required>
        </div>
        <button type="submit" class="btn btn-primary">更新出版社管理員</button>
    </form>
@endsection
