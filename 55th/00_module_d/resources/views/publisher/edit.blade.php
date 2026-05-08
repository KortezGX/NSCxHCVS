@extends('app')

@section('title', '編輯出版社')

@section('content')
    <h1>編輯出版社</h1>
    <form action="{{ route('publishers.update', $publisher) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">名稱</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $publisher->name }}" required>
        </div>
        <div class="form-group">
            <label for="address">地址</label>
            <input type="text" class="form-control" id="address" name="address" value="{{ $publisher->address }}" required>
        </div>
        <div class="form-group">
            <label for="phone">電話</label>
            <input type="text" class="form-control" id="phone" name="phone" value="{{ $publisher->phone }}" required>
        </div>
        <div class="form-group">
            <label for="isbn_code">出版社代碼</label>
            <input type="text" class="form-control" id="isbn_code" name="isbn_code" value="{{ $publisher->isbn_code }}" required>
        </div>
        <div class="form-group">
            <label for="is_active">啟用狀態</label>
            <select class="form-control" id="is_active" name="is_active">
                <option value="1" {{ $publisher->is_active ? 'selected' : '' }}>啟用</option>
                <option value="0" {{ !$publisher->is_active ? 'selected' : '' }}>停用</option>
            </select>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5>聯絡人資訊</h5>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addContact()">+ 增加聯絡人</button>
        </div>

        <div id="contact-area">
            {{-- 迴圈顯示所有聯絡人 --}}
            @foreach ($publisher->contacts as $index => $contact)
                <div class="row mb-2">
                    <div class="col"><input type="text" name="contacts[{{ $index }}][name]" class="form-control" placeholder="姓名" value="{{ $contact->name }}" required></div>
                    <div class="col"><input type="text" name="contacts[{{ $index }}][phone]" class="form-control" placeholder="電話" value="{{ $contact->phone }}" required></div>
                    <div class="col"><input type="email" name="contacts[{{ $index }}][email]" class="form-control" placeholder="Email" value="{{ $contact->email }}" required></div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.parentElement.remove()">刪除</button> {{-- 刪除按鈕 --}}
                    </div>
                </div>
            @endforeach
        </div>
        <button type="submit" class="btn btn-primary">更新出版社</button>
    </form>
@endsection

<script>
    let contactIndex = 1; // 從 1 開始，因為第一筆已經使用了 index 0

    function addContact() {
        const html = `
        <div class="row mb-2">
            <div class="col"><input type="text" name="contacts[${contactIndex}][name]" class="form-control" placeholder="姓名"></div>
            <div class="col"><input type="text" name="contacts[${contactIndex}][phone]" class="form-control" placeholder="電話"></div>
            <div class="col"><input type="email" name="contacts[${contactIndex}][email]" class="form-control" placeholder="Email"></div>
        </div>`;
        document.getElementById('contact-area').insertAdjacentHTML('beforeend', html);
        contactIndex++;
    }
</script>
