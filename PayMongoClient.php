<?php
/**
 * PayMongo API Client
 * Handles checkout session creation and payment verification
 * 
 * Usage:
 * $client = new PayMongoClient(PAYMONGO_SECRET_KEY);
 * $session = $client->createCheckoutSession($payload);
 * $status = $client->retrieveCheckoutSession($sessionId);
 */

class PayMongoClient
{
    private string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Create a checkout session
     * @param array $payload Checkout session data
     * @return array PayMongo response
     * @throws RuntimeException on error
     */
    public function createCheckoutSession(array $payload): array
    {
        return $this->request('POST', 'https://api.paymongo.com/v1/checkout_sessions', $payload);
    }

    /**
     * Retrieve checkout session status
     * @param string $checkoutSessionId Session ID
     * @return array PayMongo response
     * @throws RuntimeException on error
     */
    public function retrieveCheckoutSession(string $checkoutSessionId): array
    {
        $id = rawurlencode($checkoutSessionId);
        return $this->request('GET', "https://api.paymongo.com/v1/checkout_sessions/{$id}", null);
    }

    /**
     * Make HTTP request to PayMongo API
     * @param string $method HTTP method (GET, POST)
     * @param string $url API endpoint URL
     * @param array|null $payload Request payload
     * @return array Response data
     * @throws RuntimeException on error
     */
    private function request(string $method, string $url, ?array $payload): array
    {
        // Check if cURL is available
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL is not enabled. Enable extension=curl in php.ini');
        }

        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL.');
        }

        $headers = ['Content-Type: application/json'];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES));
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $errno !== 0) {
            throw new RuntimeException('PayMongo request failed: ' . $err);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('PayMongo returned invalid JSON.');
        }

        if ($status < 200 || $status >= 300) {
            $message = $decoded['errors'][0]['detail'] ?? 'PayMongo error (HTTP ' . $status . ')';
            throw new RuntimeException($message);
        }

        return $decoded;
    }
}
