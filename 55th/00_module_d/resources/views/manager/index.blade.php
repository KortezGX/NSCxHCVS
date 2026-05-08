@extends('app')

@section('title', '出版社管理員列表')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">出版社管理員列表</h1>
        <a href="{{ route('managers.create') }}" class="btn btn-primary">新增出版社管理員</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>帳號</th>
                    <th>密碼</th>
                    <th>姓名</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($managers as $manager)
                    <tr>
                        <td>{{ $manager->id }}</td>
                        <td>{{ $manager->account }}</td>
                        <td>{{ $manager->password }}</td>
                        <td>{{ $manager->name }}</td>
                        <td>
                            <a href="{{ route('managers.edit', $manager) }}" class="btn btn-sm btn-outline-primary">修改</a>
                            <form action="{{ route('managers.destroy', $manager) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">刪除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
@endsection
