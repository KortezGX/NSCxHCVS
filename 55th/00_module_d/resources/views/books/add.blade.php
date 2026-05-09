@extends('app')

@section('title', '新增書籍')

@section('content')
    <h1>新增書籍</h1>
    <form action="{{ route('books.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group mb-3">
            <label for="publisher_id">出版社</label>
            <select class="form-control" id="publisher_id" name="publisher_id" required>
                @foreach ($publishers as $publisher)
                    <option value="{{ $publisher->id }}">{{ $publisher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group mb-3">
            <label for="title">書籍名稱</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        <div class="form-group mb-3">
            <label for="author">作者</label>
            <input type="text" class="form-control" id="author" name="author" required>
        </div>
        <div class="form-group mb-3">
            <label for="description">描述</label>
            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
        </div>
        <div class="form-group mb-3">
            <label for="isbn">ISBN</label>
            <input type="text" class="form-control" id="isbn" name="isbn" required>
        </div>
        <div class="form-group mb-3">
            <label for="cover_image">書籍封面</label>
            <input type="file" class="form-control" id="images[]" name="images[]" multiple>
        </div>
        <button type="button" class="btn btn-danger" onclick="clearFiles()">取消上傳圖片</button>
        <button type="submit" class="btn btn-primary">新增書籍</button>
    </form>
@endsection

<script>
    function clearFiles() {
        // 找到你的 input (注意你的 id 是 images[])
        const input = document.getElementById('images[]');
        input.value = ''; // 直接設為空字串，就會清空已選取的檔案
    }
</script>
