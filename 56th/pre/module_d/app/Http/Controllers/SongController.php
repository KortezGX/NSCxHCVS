<?php

namespace App\Http\Controllers;

use App\Models\Song;
// 先在最外層引用
use App\Models\Album;
use App\Models\Label;
use Illuminate\Http\Request;

class SongController extends Controller
{
    // 6. 取得專輯內歌曲 (GET /api/albums/{album_id}/songs)
    public function index($album_id)
    {
        // [404]
        $album = Album::find($album_id);
        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // with('labels') 預先撈好標籤關聯，避免 map 裡面每首歌都各查一次 (N+1)
        $songs = Song::with('labels')->where('album_id', $album->album_id)->orderBy('track_order', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $songs->map(fn($song) => [
                'id'               => $song->song_id,
                'album_id'         => $song->album_id,
                'title'            => $song->title,
                'label'            => $song->label,
                'duration_seconds' => $song->duration_seconds,
                'order'            => $song->track_order,
                'is_cover'         => $song->is_cover,
                'cover_image_url'  => $song->cover_image_url,
            ]),
        ], 200);
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

        // 3. 題目規範：驗證是否屬於這 8 大英文預設標籤（對齊 module_c_db.sql 的 labels 表）
        $allowedLabels = ['Pop', 'Rock', 'Hip-Hop', 'Electronic', 'Jazz', 'Classical', 'Chill', 'Country'];
        $finalLabels = [];

        if ($request->has('label') && !empty($request->input('label'))) {
            // 依逗號拆開
            $inputTags = explode(',', $request->input('label'));

            foreach ($inputTags as $tag) {
                $trimmedTag = trim($tag);

                // 不在 8 大預設標籤裡就噴 400
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
        $currentSongsCount = Song::where('album_id', $album->album_id)->count();
        $nextOrder = $currentSongsCount + 1;

        // 5. 寫入資料庫
        $song = new Song();
        $song->album_id         = $album->album_id;
        $song->title            = $request->input('title');
        $song->duration_seconds = (int)$request->input('duration_seconds');
        $song->lyrics           = $request->input('lyrics');
        $song->track_order      = $nextOrder;
        $song->view_count       = 0;
        $song->is_cover         = $request->boolean('is_cover'); // Laravel 會自動把 "true"/"false" 字串轉成 boolean
        $song->cover_image_path = $imagePath;
        $song->save();

        // 曲風標籤改存關聯表：查出名稱對應的 label_id，用 sync() 寫進 song_labels
        if (!empty($finalLabels)) {
            $song->labels()->sync(Label::whereIn('name', $finalLabels)->pluck('label_id'));
        }

        // 6. [201 Created] 回傳
        return response()->json([
            'success' => true,
            'data' => [
                'id'               => $song->song_id,
                'album_id'         => $song->album_id,
                'title'            => $song->title,
                'duration_seconds' => $song->duration_seconds,
                'lyrics'           => $song->lyrics,
                'order'            => $song->track_order,
                'view_count'       => $song->view_count,
                'label'            => $song->label, // 由 Song::getLabelAttribute() 從關聯表組成純字串陣列
                'is_cover'         => $song->is_cover,
                'cover_image_url'  => $song->cover_image_url,
                'created_at'       => $song->created_at->toISOString(),
                'updated_at'       => $song->updated_at->toISOString(),
            ]
        ], 201);
    }

    // 7. 取得所有歌曲 (GET /api/songs)
    public function all(Request $request)
    {
        $limit = $request->query('limit', 10);
        $cursor = $request->query('cursor');
        $keyword = $request->query('keyword');
        $lastId = 0;

        // [400] limit 不是數字，或超出 1~100
        if ($limit !== null && (!is_numeric($limit) || (int)$limit < 1 || (int)$limit > 100)) {
            return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
        }

        // 解析 cursor（跟其他分頁 API 同一套邏輯）
        if ($cursor) {
            $decodedBase64 = base64_decode($cursor, true);
            $cursorData = json_decode($decodedBase64);

            if ($decodedBase64 === false || !$cursorData || !isset($cursorData->id)) {
                return response()->json(['success' => false, 'message' => 'Invalid cursor'], 400);
            }

            $lastId = $cursorData->id;
        }

        // with(['labels', 'album']) 預先撈好標籤跟專輯名稱，避免 map 裡面每首歌都各查一次 (N+1)
        $query = Song::with(['labels', 'album'])->where('song_id', '>', $lastId);

        // keyword 只用來比對歌名
        if (!empty($keyword)) {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        $songs = $query->orderBy('song_id', 'asc')->take($limit + 1)->get();

        $hasNextPage = $songs->count() > $limit;
        if ($hasNextPage) {
            $songs = $songs->take($limit);
        }

        $nextCursor = null;
        if ($hasNextPage && $songs->isNotEmpty()) {
            $nextCursor = base64_encode(json_encode(['id' => $songs->last()->song_id]));
        }

        $prevCursor = null;
        if ($lastId > 0 && $songs->isNotEmpty()) {
            $prevCursor = base64_encode(json_encode(['id' => $lastId - 1]));
        }

        return response()->json([
            'success' => true,
            'data' => $songs->map(fn($song) => [
                'id'               => $song->song_id,
                'album_id'         => $song->album_id,
                'title'            => $song->title,
                'label'            => $song->label,
                'duration_seconds' => $song->duration_seconds,
                'album_title'      => $song->album->title,
                'cover_image_url'  => $song->cover_image_url,
            ]),
            'meta' => [
                'next_cursor' => $nextCursor,
                'prev_cursor' => $prevCursor,
            ]
        ], 200);
    }

    // 10. 取得歌曲資訊 (GET /api/songs/{song_id})
    public function show($song_id)
    {
        // [404] 找不到歌曲
        $song = Song::find($song_id);
        if (!$song) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // 題目規定：每次取得歌曲資訊，瀏覽次數要遞增
        $song->view_count = $song->view_count + 1;
        $song->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id'               => $song->song_id,
                'album_id'         => $song->album_id,
                'title'            => $song->title,
                'duration_seconds' => $song->duration_seconds,
                'order'            => $song->track_order,
                'label'            => $song->label,
                'view_count'       => $song->view_count,
                'is_cover'         => $song->is_cover,
                'lyrics'           => $song->lyrics,
                'cover_image_url'  => $song->cover_image_url,
                'created_at'       => $song->created_at->toISOString(),
                'updated_at'       => $song->updated_at->toISOString(),
            ],
        ], 200);
    }

    // 21. 更新歌曲訊息 (POST /api/albums/{album_id}/songs/{song_id})
    public function update(Request $request, $album_id, $song_id)
    {
        // [404] 專輯不存在
        $album = Album::find($album_id);
        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // [404] 歌曲不存在，或不屬於這張專輯
        $song = Song::where('song_id', $song_id)->where('album_id', $album->album_id)->first();
        if (!$song) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // 題目規範：驗證是否屬於這 8 大英文預設標籤（跟第 19 題新增歌曲同一套規則）
        $allowedLabels = ['Pop', 'Rock', 'Hip-Hop', 'Electronic', 'Jazz', 'Classical', 'Chill', 'Country'];
        $finalLabels = [];

        if ($request->has('label') && !empty($request->input('label'))) {
            $inputTags = explode(',', $request->input('label'));

            foreach ($inputTags as $tag) {
                $trimmedTag = trim($tag);

                if (!in_array($trimmedTag, $allowedLabels)) {
                    return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
                }

                if (!in_array($trimmedTag, $finalLabels)) {
                    $finalLabels[] = $trimmedTag;
                }
            }
        }

        // 更新部分：只有帶了這個欄位才更新，沒帶的欄位維持原樣
        if ($request->has('title')) {
            $song->title = $request->input('title');
        }
        if ($request->has('duration_seconds')) {
            $song->duration_seconds = (int)$request->input('duration_seconds');
        }
        if ($request->has('lyrics')) {
            $song->lyrics = $request->input('lyrics');
        }
        if ($request->has('is_cover')) {
            $song->is_cover = $request->boolean('is_cover');
        }

        // 有上傳新圖片才覆蓋，沒有就維持原本的封面
        if ($request->hasFile('cover_image') && $request->file('cover_image')->isValid()) {
            $song->cover_image_path = $request->file('cover_image')->store('covers');
        }

        $song->save();

        // 有帶 label 才重新同步關聯，沒帶就維持原本的標籤
        if ($request->has('label')) {
            $song->labels()->sync(Label::whereIn('name', $finalLabels)->pluck('label_id'));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'               => $song->song_id,
                'album_id'         => $song->album_id,
                'title'            => $song->title,
                'duration_seconds' => $song->duration_seconds,
                'lyrics'           => $song->lyrics,
                'order'            => $song->track_order,
                'view_count'       => $song->view_count,
                'label'            => $song->label,
                'is_cover'         => $song->is_cover,
                'cover_image_url'  => $song->cover_image_url,
                'created_at'       => $song->created_at->toISOString(),
                'updated_at'       => $song->updated_at->toISOString(),
            ],
        ], 200);
    }

    // 22. 自專輯刪除歌曲 (DELETE /api/albums/{album_id}/songs/{song_id})
    public function destroy($album_id, $song_id)
    {
        // [404] 專輯不存在
        $album = Album::find($album_id);
        if (!$album) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        // [404] 歌曲不存在，或不屬於這張專輯
        $song = Song::where('song_id', $song_id)->where('album_id', $album->album_id)->first();
        if (!$song) {
            return response()->json(['success' => false, 'message' => 'Not Found'], 404);
        }

        $song->delete(); // 軟刪除

        return response()->json(['success' => true], 200);
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
