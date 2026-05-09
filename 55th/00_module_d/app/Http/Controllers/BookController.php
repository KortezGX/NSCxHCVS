<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Publisher; // 引入 Publisher Model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate; // 引入 Gate Facade 以使用授權功能
use Illuminate\Validation\ValidationException; // 引入 ValidationException 以使用驗證例外處理

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        // 判斷使用者是否具有管理員權限
        if (Gate::allows('isAdmin')) {
            // 如果是管理員，顯示所有書籍資料或是查詢資料
            if ($search) {
                $books = Book::where('title', 'like', '%' . $search . '%')->get();
            } else {
                $books = Book::all();
            }
        } else {
            // 如果不是管理員，顯示使用者所屬出版社的書籍資料或是查詢資料
            if ($search) {
                $books = Book::where('publisher_id', auth()->user()->publisher_id)
                    ->where('title', 'like', '%' . $search . '%')
                    ->get();
            } else {
                $books = Book::where('publisher_id', auth()->user()->publisher_id)->get();
            }
        }
        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // 顯示建立書籍的表單 先判斷使用者是否具有管理員權限
        if (Gate::allows('isAdmin')) {
            // 如果是管理員，顯示所有出版社資料供選擇
            $publishers = Publisher::all();
            return view('books.add', compact('publishers'));
        } else {
            // 如果不是管理員，直接使用使用者所屬出版社
            $publishers = Publisher::where('id', auth()->user()->publisher_id)->get();
            return view('books.add', compact('publishers'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 新增書籍資料
        $book = new Book();
        $book->publisher_id = $request->input('publisher_id'); // 從表單中取得出版社 ID
        $book->title = $request->input('title');
        $book->author = $request->input('author');
        $book->description = $request->input('description');

        // 呼叫內部方法計算 ISBN 13 碼的校驗碼並回傳完整的 ISBN 13 碼 帶 - 字號分隔
        $isbn = $this->checkIsbn($request->input('isbn'));
        // 檢查是否有重複
        if (Book::where('isbn', $isbn)->get()){
            throw ValidationException::withMessages(['isbn' => 'ISBN 重複']);
        }
        $book->isbn = $isbn;

        // 判斷是否有上傳封面圖片，如果有則儲存圖片並將路徑存入資料庫
        if ($request->hasFile('images')) {
            $path = [];
            foreach ($request->file('images') as $image) {
                $path[] = $image->store('upload', 'public'); // 儲存圖片並取得路徑
            }
            $book->images = $path; // 將圖片路徑存入資料庫
        }
        $book->save();

        return redirect()->route('books.index')->with('success', '書籍新增成功');
    }

    // 內部方法 計算 ISBN 13 碼的校驗碼並回傳完整的 ISBN 13 碼 帶 - 字號分隔
    private function checkIsbn(string $isbn)
    {
        // 判斷 ISBN 13碼 以 - 字號分隔
        // 第一組 978 / 979 3位數
        // 第二組 國家語言或地區碼 3位數
        // 第三組 出版社碼 3位數
        // 第四組 書籍序號碼 3位數
        // 第五組 校驗碼 1位數

        // 移除 ISBN 中的連字符
        $digits = str_replace('-', '', $isbn);

        // 判斷 ISBN 格式是否正確 使用者只需要輸入 12 碼 校驗碼會自動計算產生
        if (strlen($digits) !== 12 || !is_numeric($digits)) {
            throw ValidationException::withMessages(['isbn' => 'ISBN 格式不正確']);
        }

        // 判斷第一組是否為 978 或 979
        $prefix = substr($digits, 0, 3);
        if ($prefix !== '978' && $prefix !== '979') {
            throw ValidationException::withMessages(['isbn' => 'ISBN 前綴碼必須為 978 或 979']);
        }

        // 判斷 出版社代碼是否與資料庫中存在的出版社代碼相符
        $publisherCode = substr($digits, 6, 3); // 從 ISBN 中提取出版社代碼
        $publisher = Publisher::where('isbn_code', $publisherCode)->first(); // 查詢資料庫中是否存在該出版社代碼
        if (!$publisher) {
            throw ValidationException::withMessages(['isbn' => '出版社代碼不存在']);
        }

        // 計算加總
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$digits[$i];
            // 偶數位數乘以 3，奇數位數乘以 1
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        // 計算校驗碼
        $checksum = (10 - ($sum % 10)) % 10;

        // 組合完整的 ISBN 13 碼 帶 - 字號分隔
        $isbn13 = substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 3) . '-' . substr($digits, 9, 3) . '-' . $checksum;

        return $isbn13;
    }

    public function detial(Book $book)
    {
        // 書籍詳情 若路由帶 /01/isbn 為公開頁面
        if (request()->routeIs('books.public')) {
            // 如果 書籍隱藏或出版社停用回傳 404
            if ($book->is_hidden || !$book->publisher->is_active) {
                return abort(404);
            }
        }
        else // 若路由僅有 /isbn 則只能本出版社與超級管理員可見
        {
            // 判斷使用者是否具有管理員權限
            if (!Gate::allows('isAdmin')) {
                // 不是管理員 判斷是否登入以及出版社符合
                $user = auth()->user();

                if (!$user || $book->publisher_id !== $user->publisher_id) {
                    return abort(403);
                }
            }
        }

        // 顯示單一書籍資料
        return view('books.detial', compact('book'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        // 判斷使用者是否具有管理員權限
        if (!Gate::allows('isAdmin')) {
            // 如果不是管理員，判斷是否屬於同出版社書籍
            if ($book->publisher_id !== auth()->user()->publisher_id) {
                return abort(403);
            }
        }
        // 設定書籍隱藏顯示狀態
        $book->is_hidden = !$book->is_hidden;
        $book->save();

        return redirect()->route('books.index')->with('success', '書籍狀態已更新！');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        // 顯示編輯書籍的表單
        if (Gate::allows('isAdmin')) {
            // 如果是管理員，顯示所有出版社資料供選擇
            $publishers = Publisher::all();
        } else {
            // 如果不是管理員，直接使用使用者所屬出版社
            $publishers = Publisher::where('id', auth()->user()->publisher_id)->get();
        }
        return view('books.edit', compact('book', 'publishers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        // 更新書籍

        // 圖片處理 - 刪除
        $images = $book->images ?? [];
        if ($request->has('delete_images')) {
            $toDelete = $request->input('delete_images'); // 這是一個包含要刪除路徑的陣列

            foreach ($toDelete as $path) {
                // A. 從目前的圖片陣列中移除該路徑
                if (($key = array_search($path, $images)) !== false) {
                    unset($images[$key]);
                }

                // B. 刪除實體檔案 (避免佔用空間)
                // 注意：public_path 內容要對應你存入資料庫的字串格式
                // 如果你存的是 "upload/xxx.jpg"，通常檔案在 public/upload/xxx.jpg
                $fullPath = public_path($path);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            // C. 重要！重整陣列索引，否則 JSON 會變成物件格式導致報錯
            $images = array_values($images);
        }

        // 圖片處理 - 新增
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('upload', 'public'); // 儲存圖片並取得路徑
            }
        }
        // 判斷是否有上傳封面圖片，如果有則儲存圖片並將路徑存入資料庫
        if ($request->hasFile('images')) {
            $path = [];
            foreach ($request->file('images') as $image) {
                $path[] = $image->store('upload', 'public'); // 儲存圖片並取得路徑
            }
            $images[] = $path; // 將圖片路徑 push 到刪除後的路徑
        }

        // 更新書籍資料
        $book->title = $request->input('title');
        $book->images = $images;
        $book->save();

        return redirect()->route('books.index')->with('success', '書籍已更新！');;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        // 刪除書籍
        // 判斷使用者是否具有管理員權限
        if (!Gate::allows('isAdmin')) {
            // 如果不是管理員，判斷是否屬於同出版社書籍
            if ($book->publisher_id !== auth()->user()->publisher_id) {
                return abort(403);
            }
        }
        $book->delete();
        return redirect()->route('books.index')->with('success', '書籍已刪除！');
    }
}
