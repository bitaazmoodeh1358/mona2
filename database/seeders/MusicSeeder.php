<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Artist;
use App\Models\File;
use App\Models\Folder;
use App\Models\PlayHistory;
use App\Models\PopularArtist;
use App\Models\Song;
use App\Models\TrendSong;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MusicSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- خواننده‌ها ----------
        $artistNames = [
            'Maroon 5', 'The Chainsmokers', 'Charlee', 'Eminem',
            'Metallica', 'Lady Gaga', 'Linkin Park', 'Ed Sheeran',
        ];

        $artists = [];
        foreach ($artistNames as $name) {
            $image = $this->makeFile('image', Str::slug($name) . '.jpg', 'image/jpeg');
            $artists[$name] = Artist::create([
                'name'     => $name,
                'image_id' => $image->id,
            ]);
        }

        // ---------- آهنگ‌ها: [عنوان, آلبوم یا null برای سینگل, خواننده‌ها, مدت به ثانیه] ----------
        $songData = [
            ['Inside Out',       null,               ['The Chainsmokers', 'Charlee'], 195],
            ['The Sun',          'Songs About Jane', ['Maroon 5'], 190],
            ['Denim Jacket',     'Red Pill Blues',   ['Maroon 5'], 210],
            ['Animals',          'V',                ['Maroon 5'], 231],
            ['Plastic Rose',     'Red Pill Blues',   ['Maroon 5'], 205],
            ['What Lovers Do',   'Red Pill Blues',   ['Maroon 5'], 200],
            ['Misery',           'Hands All Over',   ['Maroon 5'], 216],
            ['Beautiful Goodbye','Overexposed',      ['Maroon 5'], 228],
            ['Shoot Love',       'V',                ['Maroon 5'], 214],
            ['Beach House',      null,               ['The Chainsmokers'], 227],
            ['Young',            null,               ['The Chainsmokers'], 244],
            ['Setting Fires',    null,               ['The Chainsmokers'], 230],
        ];

        // ---------- آلبوم‌ها ----------
        $albums = [];
        foreach ($songData as [$title, $albumTitle, $songArtists]) {
            if ($albumTitle && ! isset($albums[$albumTitle])) {
                $cover = $this->makeFile('image', Str::slug($albumTitle) . '.jpg', 'image/jpeg');
                $album = Album::create([
                    'title'          => $albumTitle,
                    'cover_image_id' => $cover->id,
                ]);
                $album->artists()->attach($artists[$songArtists[0]]->id, ['display_order' => 0]);
                $albums[$albumTitle] = $album;
            }
        }

        // ---------- ساخت آهنگ‌ها ----------
        $songs = [];
        foreach ($songData as [$title, $albumTitle, $songArtists, $duration]) {
            $audio = $this->makeFile('audio', Str::slug($title) . '.mp3', 'audio/mpeg');
            $cover = $this->makeFile('image', Str::slug($title) . '-cover.jpg', 'image/jpeg');

            $song = Song::create([
                'title'          => $title,
                'duration'       => $duration,
                'album_id'       => $albumTitle ? $albums[$albumTitle]->id : null,
                'audio_file_id'  => $audio->id,
                'cover_image_id' => $cover->id,
                'play_count'     => rand(50, 5000),
            ]);

            foreach ($songArtists as $order => $artistName) {
                $song->artists()->attach($artists[$artistName]->id, ['display_order' => $order]);
            }

            $songs[] = $song;
        }

        // ---------- Popular Artist (لیست دستی) ----------
        foreach (array_slice(array_values($artists), 0, 6) as $artist) {
            PopularArtist::create(['artist_id' => $artist->id]);
        }

        // ---------- Trending Songs ----------
        foreach (array_slice($songs, 3, 6) as $song) {
            TrendSong::create([
                'song_id'     => $song->id,
                'trend_score' => round(rand(100, 9999) / 10, 2),
            ]);
        }
// ---------- کاربرها ----------
        $me = User::create([
            'telegram_id' => 1,
            'public_id'   => 'testuser000000000001a',
            'first_name'  => 'Test',
        ]);

        $friends = [];
        foreach (['Jack', 'AMAD', 'Nina', 'Sara', 'Isack', 'Maria'] as $i => $name) {
            $friends[] = User::create([
                'telegram_id' => 100 + $i,
                'public_id'   => Str::lower(Str::random(21)),
                'first_name'  => $name,
            ]);
        }

        // دوستی دوطرفه با سه نفر اول
        foreach (array_slice($friends, 0, 3) as $friend) {
            $me->friends()->attach($friend->id);
            $friend->friends()->attach($me->id);
        }

        // ---------- فولدرها ----------
        $sad = Folder::create([
            'owner_id'   => $me->id,
            'name'       => 'Sad Song',
            'icon'       => 'light-bulb',
            'color'      => '#1B4D3E',
            'is_private' => false,
        ]);
        $sad->songs()->attach(collect($songs)->take(6)->pluck('id')->all());

        $mine = Folder::create([
            'owner_id'   => $me->id,
            'name'       => 'My Songs',
            'icon'       => 'headphones',
            'color'      => '#4D1B2E',
            'is_private' => true,
        ]);
        $mine->songs()->attach(collect($songs)->slice(6, 3)->pluck('id')->all());

        $liked = Folder::create([
            'owner_id'   => $me->id,
            'name'       => 'Like',
            'icon'       => 'heart',
            'color'      => 'red',
            'is_private' => true,
            'system_key' => 'liked',
        ]);
        $liked->songs()->attach(collect($songs)->take(3)->pluck('id')->all());

        // ---------- Recent Play ----------
        foreach (array_slice($songs, 0, 5) as $i => $song) {
            PlayHistory::create([
                'user_id'   => $me->id,
                'song_id'   => $song->id,
                'played_at' => now()->subMinutes(($i + 1) * 10),
            ]);
        }

        // توکن تست برای کاربر اصلی
        $token = $me->createToken('test')->plainTextToken;
        $this->command->info("Test token: {$token}");
    }

    private function makeFile(string $type, string $name, string $mime): File
    {
        return File::create([
            'file_name'        => $name,
            'file_type'        => $type,
            'mime_type'        => $mime,
            'file_size'        => rand(50_000, 5_000_000),
            'telegram_file_id' => 'fake_' . Str::random(30),
        ]);
    }
}
