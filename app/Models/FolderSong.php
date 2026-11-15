<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FolderSong extends Model
{
    use HasFactory;

    protected $table = 'folder_songs';
    protected $fillable = ['folder_id', 'song_id'];

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function song()
    {
        return $this->belongsTo(Song::class);
    }
}
