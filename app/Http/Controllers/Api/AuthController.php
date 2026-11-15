<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request, TelegramAuthService $telegram): JsonResponse
    {
        $request->validate([
            'init_data' => ['required', 'string'],
        ]);

        try {
            $tg = $telegram->validate($request->input('init_data'));
        } catch (RuntimeException $e) {
            return $this->error('Invalid Telegram data.', 401);
        }

        $attributes = [
            'first_name'    => $tg['first_name'] ?? 'User',
            'last_name'     => $tg['last_name'] ?? null,
            'username'      => $tg['username'] ?? null,
            'language_code' => $tg['language_code'] ?? null,
            'is_premium'    => (bool) ($tg['is_premium'] ?? false),
        ];

        $user = User::where('telegram_id', $tg['id'])->first();

        if ($user) {
            $user->update($attributes);
        } else {
            $user = User::create($attributes + [
                    'telegram_id' => $tg['id'],
                    'public_id'   => $this->generatePublicId(),
                ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('telegram')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user'  => $this->userData($user),
        ], 'Logged in.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success($this->userData($request->user()));
    }

    private function userData(User $user): array
    {
        return [
            'id'         => $user->id,
            'public_id'  => $user->public_id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'username'   => $user->username,
            'is_premium' => $user->is_premium,
        ];
    }

    private function generatePublicId(): string
    {
        do {
            $id = Str::lower(Str::random(21));
        } while (User::where('public_id', $id)->exists());

        return $id;
    }
}
