<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    public function show(File $file): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        $path = 'telegram-cache/' . $file->id;

        if (! $disk->exists($path)) {
            $token = config('services.telegram.bot_token');

            try {
                $info = Http::timeout(15)->get(
                    "https://api.telegram.org/bot{$token}/getFile",
                    ['file_id' => $file->telegram_file_id]
                );

                if (! $info->ok() || ! $info->json('ok')) {
                    abort(404);
                }

                $telegramPath = $info->json('result.file_path');

                $content = Http::timeout(120)->get(
                    "https://api.telegram.org/file/bot{$token}/{$telegramPath}"
                );
            } catch (ConnectionException $e) {
                abort(502, 'Cannot reach Telegram.');
            }

            if (! $content->ok()) {
                abort(404);
            }

            $disk->put($path, $content->body());
        }

        return response()->file($disk->path($path), [
            'Content-Type'  => $file->mime_type,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
