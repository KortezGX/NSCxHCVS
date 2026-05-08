<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>圖書管理系統 - @yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        {{-- 檢查 Auth 裡有沒有使用者已經登入 --}}
        <div class="alert alert-primary mt-3">
            目前登入狀態：
                @auth
                    @can('isAdmin')
                        歡迎 超級管理員
                    @elsecan('isManager')
                        歡迎 出版社管理員 {{ Auth::user()->name }}
                    @endcan
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm ms-3">登出</button>
                    </form>
                @else
                    尚未登入
                @endauth
        </div>

        {{-- 檢查 Session 裡有沒有名為 msg 的提示 --}}
        @if (session('msg'))
            <div class="alert alert-light">
                {{ session('msg') }}
            </div>
        @endif
        {{-- 檢查 Session 裡有沒有名為 error 的提示 --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</body>

</html>
