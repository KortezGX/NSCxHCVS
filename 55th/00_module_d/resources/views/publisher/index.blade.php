@extends('app')

@section('title', '出版社列表')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">{{ $subTitle }}</h1>
        <a href="{{ route('publishers.index') }}" class="btn btn-secondary">出版社列表</a>
        <a href="{{ route('publishers.deactivate') }}" class="btn btn-secondary">停用出版社列表</a>
        <a href="{{ route('publishers.create') }}" class="btn btn-primary">新增出版社</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>名稱</th>
                    <th>地址</th>
                    <th>電話</th>
                    <th>出版社代碼</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($publishers as $publisher)
                    <tr>
                        <td>{{ $publisher->id }}</td>
                        <td>
                            <a href="{{ route('publishers.edit', $publisher) }}" class="text-decoration-none">
                                {{ $publisher->name }}
                            </a>
                        </td>
                        <td>{{ $publisher->address }}</td>
                        <td>{{ $publisher->phone }}</td>
                        <td>{{ $publisher->isbn_code }}</td>
                        <td>
                            <a href="{{ route('publishers.activate', $publisher) }}" class="btn btn-sm btn-secondary">
                                {{ $publisher->is_active ? '停用' : '啟用' }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
