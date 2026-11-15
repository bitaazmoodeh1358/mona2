<?php

namespace App\Services;

use RuntimeException;

class TelegramAuthService
{
    /**
     * initData رو اعتبارسنجی می‌کنه و اطلاعات کاربر تلگرام رو برمی‌گردونه.
     */
    public function validate(string $initData): array
    {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            throw new RuntimeException('Bot token is not configured.');
        }

        parse_str($initData, $data);

        if (! isset($data['hash'], $data['user'])) {
            throw new RuntimeException('Invalid init data.');
        }

        $hash = $data['hash'];
        unset($data['hash']);

        ksort($data);

        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        $dataCheckString = implode("\n", $pairs);

        $secretKey  = hash_hmac('sha256', $token, 'WebAppData', true);
        $calculated = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($calculated, $hash)) {
            throw new RuntimeException('Hash mismatch.');
        }

        $authDate = (int) ($data['auth_date'] ?? 0);
        $ttl      = (int) config('services.telegram.init_data_ttl');

        if ($authDate === 0 || (time() - $authDate) > $ttl) {
            throw new RuntimeException('Init data expired.');
        }

        $user = json_decode($data['user'], true);

        if (! is_array($user) || empty($user['id'])) {
            throw new RuntimeException('Invalid user data.');
        }

        return $user;
    }
}
