<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    // 16. 創建新專輯 (POST /api/albums)
    public function store(Request $request)
    {
        // 1. 取得目前登入的使用者資料 (從 Middleware 傳進來的)
        $current_user = $request->input('current_user');

        // 2. 建立一個全新的空專輯物件
        $album = new Album();

        // 3. 一對一指派欄位資料
        $album->publisher_id = $current_user->id; // 將建立者設定為當前登入的管理員 ID
        $album->title        = $request->input('title');
        $album->artist       = $request->input('artist');
        $album->release_year = (int) $request->input('release_year'); // 強制轉成整數符合型態
        $album->genre        = $request->input('genre');
        $album->description  = $request->input('description');

        // 4. 儲存進資料庫
        $album->save();

        // 5. [201 成功] 回傳符合題目要求的 JSON 格式與 201 狀態碼
        return response()->json([
            'success' => true,
            'data' => [
                'id'           => $album->id,
                'title'        => $album->title,
                'artist'       => $album->artist,
                'release_year' => $album->release_year,
                'genre'        => $album->genre,
                'description'  => $album->description,
                'publisher'    => [
                    'id'       => $current_user->id,
                    'username' => $current_user->username,
                    'email'    => $current_user->email,
                ],
                'created_at'   => $album->created_at->toISOString(), // 時間格式帶 Z
                'updated_at'   => $album->updated_at->toISOString(), // 時間格式帶 Z
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Album $album)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Album $album)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Album $album)
    {
        //
    }
}
