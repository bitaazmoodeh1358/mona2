<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlbumArtist extends Model
{
    use HasFactory;

    protected $table = 'album_artist';
    protected $fillable = ['album_id', 'artist_id'];
    public $timestamps = false;

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}
