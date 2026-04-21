<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class TextbeltSms
{
    public function send(string $phone, string $message): array
    {
        // Free example key: "textbelt" (very limited). Replace with a paid key or a PH-friendly provider.
        $key = config('services.textbelt.key', 'textbelt');

        $res = Http::asForm()->post('https://textbelt.com/text', [
            'phone' => $phone,
            'message' => $message,
            'key' => $key,
        ]);

        return $res->json() ?? ['success' => false, 'error' => 'No response'];
    }
}
