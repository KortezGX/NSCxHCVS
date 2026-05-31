<?php

namespace App\Http\Controllers;

use App\Models\Song;
// 先在最外層引用
use App\Models\Album;
use Illuminate\Http\Request;

class SongController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    // 19.新增歌曲到專輯 (POST /api/albums/{album_id}/songs)
    public function store(Request $request, $album_id)
    {
        // 1. [404 檢查]
        $album = Album::find($album_id);
        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // 2. [400 驗證] 基本欄位檢查
        if (!$request->has('title') || !$request->has('duration_seconds')) {
            return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
        }

        // 3. 題目規範：驗證是否屬於這 8 大中文預設標籤 (題目有可能是英文的，請以題目要求為主)
        $allowedLabels = ['流行', '搖滾', '嘻哈', '電子', '爵士', '經典', '紓壓', '鄉村'];
        $finalLabels = [];

        if ($request->has('label') && !empty($request->input('label'))) {
            // 依逗號拆開
            $inputTags = explode(',', $request->input('label'));

            foreach ($inputTags as $tag) {
                $trimmedTag = trim($tag);

                // 直接比對中文，不在裡面就噴 400
                if (!in_array($trimmedTag, $allowedLabels)) {
                    return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
                }

                if (!in_array($trimmedTag, $finalLabels)) {
                    $finalLabels[] = $trimmedTag;
                }
            }
        }

        // 4. 處理實體圖片上傳
        $imagePath = null;
        if ($request->hasFile('cover_image') && $request->file('cover_image')->isValid()) {
            $imagePath = $request->file('cover_image')->store('covers');
        }

        // 算一下目前這張專輯有幾首歌，直接 +1。這樣就算資料庫沒給預設值也不會爆掉！
        $currentSongsCount = Song::where('album_id', $album->id)->count();
        $nextOrder = $currentSongsCount + 1;

        // 5. 寫入資料庫
        $song = new Song();
        $song->album_id         = $album->id;
        $song->title            = $request->input('title');
        $song->duration_seconds = (int)$request->input('duration_seconds');
        $song->lyrics           = $request->input('lyrics');
        $song->order            = $nextOrder;
        $song->view_count       = 0;
        $song->label            = $finalLabels; // 直接存進去（例如：["搖滾", "流行"]）
        $song->is_cover         = $request->boolean('is_cover'); // Laravel 會自動把 "true"/"false" 字串轉成 boolean
        $song->cover_image_path = $imagePath;
        $song->save();

        // 6. [201 Created] 回傳
        return response()->json([
            'success' => true,
            'data' => [
                'id'               => $song->id,
                'album_id'         => (int)$song->album_id,
                'title'            => $song->title,
                'duration_seconds' => $song->duration_seconds,
                'lyrics'           => $song->lyrics,
                'order'            => $song->order,
                'view_count'       => $song->view_count,
                'label'            => $song->label, // 回傳純中文陣列
                'is_cover'         => $song->is_cover,
                'cover_image_url'  => $song->cover_image_url,
                'created_at'       => $song->created_at->toISOString(),
                'updated_at'       => $song->updated_at->toISOString(),
            ]
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Song $song)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Song $song)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Song $song)
    {
        //
    }

    // 8.取得歌曲封面圖片 (GET /api/songs/{song_id}/cover)
    public function showCover($song_id)
    {
        // 1. 尋找歌曲
        $song = Song::find($song_id);

        // [404 檢查] 找不到歌曲，或該歌曲根本沒上傳過圖片路徑
        if (!$song || !$song->cover_image_path) {
            return response()->json(['success' => false, 'message' => 'Cover Not Found'], 404);
        }

        // 2. 取得實體檔案的絕對路徑，19 題會把圖片路徑存在 storage 裡。
        // 註：Laravel 11+ 預設 store() 會存在 storage/app/private/
        $filePath = storage_path('app/private/' . $song->cover_image_path);

        // 檢查硬碟裡是不是真的有這個檔案
        if (!file_exists($filePath)) {
            return response()->json(['success' => false, 'message' => 'File Not Found'], 404);
        }

        // 3. 把實體圖檔轉成二進位流吐給前端（瀏覽器會直接顯示成圖片）
        return response()->file($filePath, [
            'Content-Type' => 'image/jpeg' // 確保對齊題目要求的 image/jpeg
        ]);
    }
}
