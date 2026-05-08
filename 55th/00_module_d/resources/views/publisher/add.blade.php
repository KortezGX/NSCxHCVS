@extends('app')

@section('title', '新增出版社')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">新增出版社</h1>
    </div>
    <form action="{{ route('publishers.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">名稱</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">地址</label>
            <input type="text" class="form-control" id="address" name="address" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">電話</label>
            <input type="text" class="form-control" id="phone" name="phone" required>
        </div>
        <div class="mb-3">
            <label for="isbn_code" class="form-label">出版社代碼</label>
            <input type="text" class="form-control" id="isbn_code" name="isbn_code" required>
        </div>
        <div class="mb-3">
            <label for="is_active" class="form-label">啟用狀態</label>
            <select class="form-control" id="is_active" name="is_active">
                <option value="1">啟用</option>
                <option value="0">停用</option>
            </select>
        </div>
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5>聯絡人資訊</h5>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addContact()">+ 增加聯絡人</button>
        </div>

        <div id="contact-area">
            {{-- 第一筆必填 --}}
            <div class="row mb-2">
                <div class="col"><input type="text" name="contacts[0][name]" class="form-control" placeholder="姓名" required></div>
                <div class="col"><input type="text" name="contacts[0][phone]" class="form-control" placeholder="電話" required></div>
                <div class="col"><input type="email" name="contacts[0][email]" class="form-control" placeholder="Email" required></div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">新增出版社</button>
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
