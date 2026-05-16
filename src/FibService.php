<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

class FibService {
    private static ?string $cachedToken = null;
    private static int $tokenExpiresAt = 0;

    private static function baseUrl(): string {
        return rtrim((string)env('FIB_BASE_URL', 'https://fib.stage.fib.iq'), '/');
    }

    private static function getAccessToken(): string {
        if (self::$cachedToken !== null && time() < self::$tokenExpiresAt) {
            return self::$cachedToken;
        }

        $body = http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => (string)env('FIB_CLIENT_ID'),
            'client_secret' => (string)env('FIB_CLIENT_SECRET'),
        ]);

        $ch = curl_init(self::baseUrl() . '/auth/realms/fib-online-shop/protocol/openid-connect/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("FIB auth error: $curlError");
        }

        $data = json_decode((string)$response, true);

        if ($httpCode >= 400 || !isset($data['access_token'])) {
            $msg = $data['error_description'] ?? $data['error'] ?? "HTTP $httpCode";
            throw new Exception("FIB authentication failed: $msg");
        }

        // Subtract 30s buffer to avoid using a token right at expiry
        self::$cachedToken    = $data['access_token'];
        self::$tokenExpiresAt = time() + (int)($data['expires_in'] ?? 300) - 30;

        return self::$cachedToken;
    }

    private static function request(string $method, string $path, ?array $body = null): ?array {
        $token   = self::getAccessToken();
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ];

        $ch = curl_init(self::baseUrl() . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => 15,
        ];

        if ($body !== null) {
            $json       = json_encode($body);
            $headers[]  = 'Content-Type: application/json';
            $headers[]  = 'Content-Length: ' . strlen($json);
            $opts[CURLOPT_POSTFIELDS] = $json;
        }

        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("FIB request error: $curlError");
        }

        // 204 No Content (cancel) and 202 Accepted (refund) return no body
        if ($httpCode === 204 || $httpCode === 202) {
            return null;
        }

        $data = json_decode((string)$response, true);

        if ($httpCode >= 400) {
            $msg = $data['message'] ?? $data['error'] ?? "HTTP $httpCode";
            throw new Exception("FIB Error: $msg");
        }

        return $data;
    }

    public static function createPayment(int $amount, string $description, ?string $callbackUrl = null, ?string $redirectUrl = null): array {
        $payload = [
            'monetaryValue' => ['amount' => $amount, 'currency' => 'IQD'],
        ];
        if ($description !== '') {
            $payload['description'] = $description;
        }
        if ($callbackUrl !== null) {
            $payload['statusCallbackUrl'] = $callbackUrl;
        }
        if ($redirectUrl !== null) {
            $payload['redirectUrl'] = $redirectUrl;
        }

        return self::request('POST', '/protected/v1/payments', $payload) ?? [];
    }

    public static function getPaymentStatus(string $paymentId): array {
        return self::request('GET', "/protected/v1/payments/{$paymentId}/status") ?? [];
    }

    public static function cancelPayment(string $paymentId): void {
        self::request('POST', "/protected/v1/payments/{$paymentId}/cancel");
    }

    public static function refundPayment(string $paymentId): void {
        self::request('POST', "/protected/v1/payments/{$paymentId}/refund");
    }
}
