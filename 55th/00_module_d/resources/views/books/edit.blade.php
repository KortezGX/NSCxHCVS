@extends('app')

@section('title', '編輯書籍')

@section('content')
    <h1>編輯書籍</h1>
    <form action="{{ route('books.update', $book) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="publisher_id">所屬出版社</label>
            <select class="form-control" id="publisher_id" name="publisher_id" required>
                @foreach ($publishers as $publisher)
                    <option value="{{ $publisher->id }}" {{ $book->publisher_id == $publisher->id ? 'selected' : '' }}>
                        {{ $publisher->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="title">書籍名稱</label>
            <input type="text" class="form-control" id="title" name="title" value="{{ $book->title }}" required>
        </div>
        <div class="form-group">
            <label for="description">描述</label>
            <input type="text" class="form-control" id="description" name="description" value="{{ $book->description }}" required>
        </div>
        <div class="form-group">
            <label for="author">作者</label>
            <input type="text" class="form-control" id="author" name="author" value="{{ $book->author }}" required>
        </div>
        <div class="form-group">
            <label for="isbn">ISBN</label>
            <input type="text" class="form-control" id="isbn" name="isbn" value="{{ $book->isbn }}" required>
        </div>
        <div class="form-group mb-3">
            <label for="cover_image">書籍封面</label>
            <input type="file" class="form-control" id="images[]" name="images[]" multiple>
        </div>
        <button type="button" class="btn btn-danger" onclick="clearFiles()">取消上傳圖片</button>
        <div class="row">
            @if($book->images)
                @foreach($book->images as $path)
                    <div class="col-md-3">
                        <img src="{{ asset('storage/' . $path) }}" class="img-thumbnail">
                        <div class="form-check mt-1">
                            <!-- 使用路徑作為 value -->
                            <input class="form-check-input" type="checkbox" name="delete_images[]" value="{{ $path }}" id="img-{{ $loop->index }}">
                            <label class="form-check-label text-danger" for="img-{{ $loop->index }}">
                                刪除
                            </label>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <button type="submit" class="btn btn-primary">更新書籍</button>
    </form>
@endsection

<script>
    function clearFiles() {
        // 找到你的 input (注意你的 id 是 images[])
        const input = document.getElementById('images[]');
        input.value = ''; // 直接設為空字串，就會清空已選取的檔案
    }
</script>
