@extends('app')

@section('title', '書籍詳細資料')

@section('content')
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>欄位名稱</th>
                    <th>欄位值</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>id</td>
                    <td>{{ $book->id }}</td>
                </tr>
                <tr>
                    <td>書籍名稱</td>
                    <td>{{ $book->title }}</td>
                </tr>
                <tr>
                    <td>書籍封面</td>
                    <td>
                        @if ($book->images)
                            <img src="{{ asset('storage/' . $book->images[0]) }}" class="img-thumbnail" style="max-width: 100px;">
                        @else
                            <img src="{{ asset('storage/upload/default.jpg') }}" class="img-thumbnail" style="max-width: 100px;">
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>描述</td>
                    <td>{{ $book->description }}</td>
                </tr>
                <tr>
                    <td>作者</td>
                    <td>{{ $book->author }}</td>
                </tr>
                <tr>
                    <td>ISBN</td>
                    <td>{{ $book->isbn }}</td>
                </tr>
                <tr>
                    <td>出版社名稱</td>
                    <td>{{ $book->publisher->name }}</td>
                </tr>
                <tr>
                    <td>其他上傳圖片</td>
                    <td>
                        @if ($book->images)
                            @foreach ($book->images as $image)
                                <img src="{{ asset('storage/' . $image) }}" class="img-thumbnail" style="max-width: 100px;">
                            @endforeach
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
