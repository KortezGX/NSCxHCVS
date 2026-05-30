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

    // 17. 更新專輯訊息 (PUT /api/albums/{album_id})
    public function update(Request $request, $album_id)
    {
        // [404 檢查] 確認資料庫是否有該專輯
        $album = Album::find($album_id);

        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // 1. 手動指派更新的欄位
        $album->title       = $request->input('title');
        $album->description = $request->input('description');

        // 2. 儲存更新後的資料回資料庫
        $album->save();

        // 3. 取得該專輯的發布者資料 (利用我們定義好的 belongsTo 關聯)
        $publisher = $album->publisher;

        // 4. [200 成功] 回傳符合題目要求的 JSON 格式回應
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
                    'id'       => $publisher->id,
                    'username' => $publisher->username,
                    'email'    => $publisher->email,
                ],
                'created_at'   => $album->created_at->toISOString(),
                'updated_at'   => $album->updated_at->toISOString(),
            ]
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Album $album)
    {
        //
    }
}
