<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'duration'  => $this->duration,
            'cover_url' => $this->cover_image_id
                ? route('files.show', $this->cover_image_id)
                : null,
            'audio_url' => route('files.show', $this->audio_file_id),
            'album'     => $this->whenLoaded(
                'album',
                fn () => $this->album?->only(['id', 'title'])
            ),
            'artists'   => ArtistResource::collection($this->whenLoaded('artists')),
        ];
    }
}

