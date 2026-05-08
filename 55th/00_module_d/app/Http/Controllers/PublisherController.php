<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use App\Models\PublisherContact; // 引入 PublisherContact Model
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 取得所有出版社資料，並傳遞給 view 顯示
        $publishers = Publisher::all();
        return view('publisher.index', compact('publishers'), ['subTitle' => '出版社列表']);
    }

    // 手動建立一個停用出版社的路由方法
    public function deactivate()
    {
        // 取得所有停用的出版社資料，並傳遞給 view 顯示
        $publishers = Publisher::where('is_active', false)->get();
        return view('publisher.index', compact('publishers'), ['subTitle' => '停用出版社列表']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // 顯示建立出版社的表單
        return view('publisher.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 驗證 isbn_code 的唯一性
        $request->validate([
            'isbn_code' => 'required|unique:publishers,isbn_code',
        ], [
            'isbn_code.unique' => '出版社代碼已存在，請使用其他代碼。',
        ]);

        // 建立新的出版社資料
        $publisher = new Publisher();
        $publisher->name = $request->input('name');
        $publisher->address = $request->input('address');
        $publisher->phone = $request->input('phone');
        $publisher->isbn_code = $request->input('isbn_code');
        $publisher->is_active = $request->input('is_active', true); // 預設為啟用
        $publisher->save();

        // 迴圈建立多筆聯絡人
        foreach ($request->contacts as $item){
            // 檢查聯絡人姓名是否為空，如果不為空則儲存
            if (!empty($item['name'])) {
                $contact = new PublisherContact();
                $contact->publisher_id = $publisher->id;
                $contact->name = $item['name'];
                $contact->phone = $item['phone'];
                $contact->email = $item['email'];
                $contact->save();
            }
        }

        // 重定向到出版社列表頁面
        return redirect()->route('publishers.index')->with('success', '出版社新增成功！');
    }

    /**
     * Display the specified resource.
     */
    public function show(Publisher $publisher)
    {
        // 設定出版社停用啟用狀態
        $publisher->is_active = !$publisher->is_active;
        $publisher->save();

        return redirect()->route('publishers.index')->with('success', '出版社狀態已更新！');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Publisher $publisher)
    {
        // 顯示編輯出版社的表單，並傳遞現有資料給 view 顯示
        return view('publisher.edit', compact('publisher'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Publisher $publisher)
    {
        // 更新出版社資料
        // 驗證 isbn_code 的唯一性，排除當前編輯的出版社
        $request->validate([
            'isbn_code' => 'required|unique:publishers,isbn_code,' . $publisher->id,
        ], [
            'isbn_code.unique' => '出版社代碼已存在，請使用其他代碼。',
        ]);

        $publisher->name = $request->input('name');
        $publisher->address = $request->input('address');
        $publisher->phone = $request->input('phone');
        $publisher->isbn_code = $request->input('isbn_code');
        $publisher->is_active = $request->input('is_active', true); // 預設為啟用
        $publisher->save();

        // 刪除原有的聯絡人資料
        $publisher->contacts()->delete();
        // 迴圈更新多筆聯絡人
        foreach ($request->contacts as $item){
            // 檢查聯絡人姓名是否為空，如果不為空則儲存
            if (!empty($item['name'])) {
                $contact = new PublisherContact();
                $contact->publisher_id = $publisher->id;
                $contact->name = $item['name'];
                $contact->phone = $item['phone'];
                $contact->email = $item['email'];
                $contact->save();
            }
        }

        // 重定向到出版社列表頁面
        return redirect()->route('publishers.index')->with('success', '出版社更新成功！');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Publisher $publisher)
    {
        //
    }
}
