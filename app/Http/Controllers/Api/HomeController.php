<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Models\PlayHistory;
use App\Models\PopularArtist;
use App\Models\Song;
use App\Models\TrendSong;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use ApiResponse;

    private const LIMIT = 10;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Recent Play: آخرین آهنگ‌های یکتای پخش‌شده توسط خود کاربر
        $recentIds = PlayHistory::where('user_id', $user->id)
            ->select('song_id', DB::raw('MAX(played_at) as last_played'))
            ->groupBy('song_id')
            ->orderByDesc('last_played')
            ->limit(self::LIMIT)
            ->pluck('song_id')
            ->all();

        $recent = Song::with('artists')
            ->whereIn('id', $recentIds)
            ->get()
            ->sortBy(fn (Song $song) => array_search($song->id, $recentIds))
            ->values();

        // Trending Songs
        $trending = TrendSong::with('song.artists')
            ->orderByDesc('trend_score')
            ->limit(self::LIMIT)
            ->get()
            ->pluck('song')
            ->filter()
            ->values();

        // Popular Artist: لیست دستی
        $popularArtists = PopularArtist::with('artist')
            ->orderBy('id')
            ->limit(self::LIMIT)
            ->get()
            ->pluck('artist')
            ->filter()
            ->values();

        // Best Songs: بیشترین پخش در کل زمان‌ها
        $best = Song::with('artists')
            ->orderByDesc('play_count')
            ->limit(self::LIMIT)
            ->get();

        return $this->success([
            'name'            => $user->first_name,
            'recent_play'     => SongResource::collection($recent),
            'trending_songs'  => SongResource::collection($trending),
            'popular_artists' => ArtistResource::collection($popularArtists),
            'best_songs'      => SongResource::collection($best),
        ]);
    }
}

