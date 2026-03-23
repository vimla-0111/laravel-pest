<?php

namespace App\Support;

use RuntimeException;

class OnlyOfficeJwt
{
    public static function encode(array $payload, string $secret): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $encodedHeader = self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedPayload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $secret, true);

        return implode('.', [
            $encodedHeader,
            $encodedPayload,
            self::base64UrlEncode($signature),
        ]);
    }

    public static function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid ONLYOFFICE token format.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $secret, true)
        );

        if (! hash_equals($expectedSignature, $encodedSignature)) {
            throw new RuntimeException('Invalid ONLYOFFICE token signature.');
        }

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);

        if (! is_array($payload)) {
            throw new RuntimeException('Invalid ONLYOFFICE token payload.');
        }

        return $payload;
    }

    protected static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected static function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid ONLYOFFICE token encoding.');
        }

        return $decoded;
    }
}
