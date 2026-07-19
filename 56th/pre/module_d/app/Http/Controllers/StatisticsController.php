<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Album;
use App\Models\Label;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    // 11. 取得統計結果 (GET /api/statistics)
    public function index(Request $request)
    {
        $metrics = $request->query('metrics');

        if ($metrics === 'song') {
            return $this->songMetrics($request);
        }

        if ($metrics === 'album') {
            return $this->albumMetrics();
        }

        if ($metrics === 'label') {
            return $this->labelMetrics($request);
        }

        // [400] metrics 不是 song/album/label 三選一
        return response()->json(['success' => false, 'message' => 'Invalid parameter'], 400);
    }

    // metrics=song：依曲風過濾（選填），依 view_count 由高到低排序
    private function songMetrics(Request $request)
    {
        $query = Song::with('labels');

        $labelsParam = $request->query('labels');
        if (!empty($labelsParam)) {
            $labelNames = array_map('trim', explode(',', $labelsParam));
            $query->whereHas('labels', fn($q) => $q->whereIn('name', $labelNames));
        }

        $songs = $query->orderBy('view_count', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $songs->map(fn($song) => $this->formatSong($song)),
        ], 200);
    }

    // metrics=album：以專輯分組，加總底下所有歌曲的 view_count，依總瀏覽數由高到低排序
    private function albumMetrics()
    {
        $albums = Album::with('publisher')->withSum('songs', 'view_count')->orderBy('songs_sum_view_count', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $albums->map(fn($album) => [
                'id'               => $album->album_id,
                'title'            => $album->title,
                'artist'           => $album->artist,
                'release_year'     => $album->release_year,
                'genre'            => $album->genre,
                'description'      => $album->description,
                'publisher'        => [
                    'id'       => $album->publisher->user_id,
                    'username' => $album->publisher->username,
                    'email'    => $album->publisher->email,
                ],
                'created_at'       => $album->created_at->format('Y-m-d\TH:i:s.v\Z'),
                'updated_at'       => $album->updated_at->format('Y-m-d\TH:i:s.v\Z'),
                'total_view_count' => (int) $album->songs_sum_view_count,
            ]),
        ], 200);
    }

    // metrics=label：以曲風分組（選填篩選特定曲風），每組最多取前 10 首瀏覽量最高的歌曲，依總瀏覽數由高到低排序
    private function labelMetrics(Request $request)
    {
        $query = Label::with(['songs' => fn($q) => $q->orderBy('view_count', 'desc')]);

        $labelsParam = $request->query('labels');
        if (!empty($labelsParam)) {
            $labelNames = array_map('trim', explode(',', $labelsParam));
            $query->whereIn('name', $labelNames);
        }

        $labels = $query->get();

        $result = $labels->map(fn($label) => [
            'total_view_count' => (int) $label->songs->sum('view_count'),
            'label'            => $label->name,
            'songs'            => $label->songs->take(10)->map(fn($song) => $this->formatSong($song))->values(),
        ])->sortByDesc('total_view_count')->values();

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    private function formatSong(Song $song)
    {
        return [
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
            'created_at'       => $song->created_at->format('Y-m-d\TH:i:s.v\Z'),
            'updated_at'       => $song->updated_at->format('Y-m-d\TH:i:s.v\Z'),
        ];
    }
}
