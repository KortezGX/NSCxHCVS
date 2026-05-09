@extends('app')

@section('title', '書籍列表')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">書籍列表</h1>
        <a href="{{ route('books.create') }}" class="btn btn-primary">新增書籍</a>
    </div>
    <div class="mb-4">
        <form action="{{ route('books.index') }}" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="搜尋書籍名稱" value="{{ request('search') }}">
            <button type="submit" class="btn btn-outline-secondary">搜尋</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>書籍名稱</th>
                    <th>書籍封面</th>
                    <th>描述</th>
                    <th>作者</th>
                    <th>ISBN</th>
                    <th>出版社名稱</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($books as $book)
                    <tr>
                        <td>{{ $book->id }}</td>
                        <td>
                            <a href="{{ route('books.detial', $book) }}" class="text-decoration-none">
                                {{ $book->title }}
                            </a>
                        </td>
                        <td>
                            @if ($book->images)
                                <img src="{{ asset('storage/' . $book->images[0]) }}" class="img-thumbnail" style="max-width: 100px;">
                            @else
                                <img src="{{ asset('storage/upload/default.jpg') }}" class="img-thumbnail" style="max-width: 100px;">
                            @endif
                        </td>
                        <td>{{ $book->description }}</td>
                        <td>{{ $book->author }}</td>
                        <td>{{ $book->isbn }}</td>
                        <td>{{ $book->publisher->name }}</td>
                        <td>
                            <a href="{{ route('books.show', $book) }}" class="btn btn-sm btn-secondary">
                                {{ $book->is_hidden ? '顯示' : '隱藏' }}
                            </a>
                            <a href="{{ route('books.edit', $book) }}" class="btn btn-sm btn-secondary">編輯</a>
                            <form action="{{ route('books.destroy', $book) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">刪除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
