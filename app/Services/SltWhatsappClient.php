<?php

namespace App\Services;

use App\Models\ApiLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SltWhatsappClient
{
    private string $base;
    private string $username;
    private string $password;
    private string $phoneNumberId;
    private ?string $bearerToken;
    private bool $verifySsl;

    public function __construct()
    {
        $cfg = config('services.slt_whatsapp');
        $this->base = rtrim($cfg['base'], '/');
        $this->username = $cfg['username'];
        $this->password = $cfg['password'];
        $this->phoneNumberId = $cfg['phone_number_id'];
        $this->bearerToken = $cfg['bearer_token'] ?? null;
        $this->verifySsl = (bool) ($cfg['verify_ssl'] ?? true);
    }

    public function phoneNumberId(): string
    {
        return $this->phoneNumberId;
    }

    public function getToken(): string
    {
        if ($this->bearerToken) {
            return $this->bearerToken;
        }

        return Cache::remember('slt_whatsapp_token', now()->addMinutes(50), function () {
            return $this->login();
        });
    }

    public function login(): string
    {
        $url = "{$this->base}/login.php";

        $start = microtime(true);
        $resp = $this->http(15)->asJson()->post($url, [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        $duration = (int) round((microtime(true) - $start) * 1000);

        $data = $this->jsonResponse($resp, '/login.php');
        $token = $data['token'] ?? $data['access_token'] ?? $data['data']['token'] ?? null;

        ApiLog::create([
            'user_id' => auth()->id(),
            'endpoint' => '/login.php',
            'method' => 'POST',
            'status' => $resp->status(),
            'duration_ms' => $duration,
            'request' => ['username' => $this->username, 'password' => '***'],
            'response' => $this->safeResponse($data),
        ]);

        if (!$resp->successful() || !$token) {
            throw new \RuntimeException($this->responseFailureMessage('/login.php', $resp->status(), $data, 'SLT login failed'));
        }

        return $token;
    }

    public function getMessages(string $mobile, int $limit = 20): array
    {
        return $this->authedPost('/getMessages.php', [
            'mobile' => $mobile,
            'limit' => $limit,
        ]);
    }

    /**
     * Fetch the most-recent active mobile numbers from SLT.
     *
     * Endpoint example:
     *   GET /getRecentActiveMobiles.php
     *   Authorization: Bearer <token>
     *
     * This method is defensive about the response shape and will attempt to
     * extract a list of mobile numbers from common keys / item shapes.
     */
    public function getRecentActiveMobiles(int $take = 5): array
    {
        $payload = $this->authedGet('/getRecentActiveMobiles.php');

        // Common response wrappers
        $items = $payload['mobiles']
            ?? $payload['recentActiveMobiles']
            ?? $payload['data']
            ?? $payload['phones']
            ?? $payload['numbers']
            ?? $payload;

        if (!is_array($items)) {
            return [];
        }

        // If items contain timestamps, sort newest-first.
        $hasTs = false;
        foreach ($items as $it) {
            if (is_array($it) && (isset($it['timestamp']) || isset($it['time']) || isset($it['last_seen']) || isset($it['lastSeen']))) {
                $hasTs = true;
                break;
            }
        }
        if ($hasTs) {
            usort($items, function ($a, $b) {
                $ta = is_array($a) ? ($a['timestamp'] ?? $a['time'] ?? $a['last_seen'] ?? $a['lastSeen'] ?? null) : null;
                $tb = is_array($b) ? ($b['timestamp'] ?? $b['time'] ?? $b['last_seen'] ?? $b['lastSeen'] ?? null) : null;
                $ta = $ta ? strtotime((string) $ta) : 0;
                $tb = $tb ? strtotime((string) $tb) : 0;
                return $tb <=> $ta;
            });
        }

        $mobiles = [];
        foreach ($items as $it) {
            $raw = null;

            if (is_string($it) || is_numeric($it)) {
                $raw = (string) $it;
            } elseif (is_array($it)) {
                $raw = $it['mobile']
                    ?? $it['msisdn']
                    ?? $it['phone']
                    ?? $it['number']
                    ?? $it['contact']
                    ?? null;
            }

            if (!$raw) continue;

            $num = preg_replace('/\D+/', '', $raw);
            if (!$num) continue;

            // Normalize 07xxxxxxxx -> 94xxxxxxxxx
            if (str_starts_with($num, '0')) {
                $num = '94' . ltrim($num, '0');
            }

            // Avoid obviously-bad values
            if (strlen($num) < 9) continue;

            $mobiles[] = $num;
        }

        $mobiles = array_values(array_unique($mobiles));

        return array_slice($mobiles, 0, max(0, $take));
    }

    public function reply(string $uuid, string $mobile, string $replyMessage): array
    {
        return $this->authedPost('/reply.php', [
            'uuid' => $uuid,
            'phone_number_id' => $this->phoneNumberId(),
            'mobile' => $mobile,
            'replymessage' => $replyMessage,
        ]);
    }

    private function authedPost(string $endpoint, array $payload): array
    {
        $token = $this->getToken();
        $url = "{$this->base}{$endpoint}";

        $start = microtime(true);
        $resp = $this->http(20)
            ->withToken($token)
            ->asJson()
            ->post($url, $payload);

        // If token expired, retry once
        if ($resp->status() === 401) {
            Cache::forget('slt_whatsapp_token');
            $token = $this->getToken();

            $start = microtime(true);
            $resp = $this->http(20)
                ->withToken($token)
                ->asJson()
                ->post($url, $payload);
        }

        $duration = (int) round((microtime(true) - $start) * 1000);

        ApiLog::create([
            'user_id' => auth()->id(),
            'endpoint' => $endpoint,
            'method' => 'POST',
            'status' => $resp->status(),
            'duration_ms' => $duration,
            'request' => $this->safeRequest($payload),
            'response' => $this->safeResponse($this->jsonResponse($resp, $endpoint)),
        ]);

        if (!$resp->successful()) {
            throw new \RuntimeException($this->responseFailureMessage($endpoint, $resp->status(), $this->jsonResponse($resp, $endpoint)));
        }

        return $this->jsonResponse($resp, $endpoint);
    }

    private function authedGet(string $endpoint, array $query = []): array
    {
        $token = $this->getToken();
        $url = "{$this->base}{$endpoint}";

        $start = microtime(true);
        $resp = $this->http(20)
            ->withToken($token)
            ->acceptJson()
            ->get($url, $query);

        // If token expired, retry once
        if ($resp->status() === 401) {
            Cache::forget('slt_whatsapp_token');
            $token = $this->getToken();

            $start = microtime(true);
            $resp = $this->http(20)
                ->withToken($token)
                ->acceptJson()
                ->get($url, $query);
        }

        $duration = (int) round((microtime(true) - $start) * 1000);

        ApiLog::create([
            'user_id' => auth()->id(),
            'endpoint' => $endpoint,
            'method' => 'GET',
            'status' => $resp->status(),
            'duration_ms' => $duration,
            'request' => $this->safeRequest($query),
            'response' => $this->safeResponse($this->jsonResponse($resp, $endpoint)),
        ]);

        if (!$resp->successful()) {
            throw new \RuntimeException($this->responseFailureMessage($endpoint, $resp->status(), $this->jsonResponse($resp, $endpoint)));
        }

        return $this->jsonResponse($resp, $endpoint);
    }

    private function safeRequest(array $payload): array
    {
        // Do not log anything sensitive if you add more fields later.
        return $payload;
    }

    private function http(int $timeout)
    {
        $pending = Http::timeout($timeout);

        return $this->verifySsl ? $pending : $pending->withoutVerifying();
    }

    private function jsonResponse($resp, string $endpoint): array
    {
        $data = $resp->json();

        if (is_array($data)) {
            return $data;
        }

        $body = trim((string) $resp->body());
        if ($body === '') {
            return [];
        }

        if (!$resp->successful()) {
            return ['_raw_body_preview' => substr($body, 0, 500)];
        }

        throw new \RuntimeException("Invalid JSON returned by SLT API {$endpoint} (status {$resp->status()}).");
    }

    private function responseFailureMessage(string $endpoint, int $status, array $data, string $prefix = 'SLT API failed'): string
    {
        $message = $data['message']
            ?? $data['error']
            ?? $data['status']['message']
            ?? null;

        $suffix = is_string($message) && trim($message) !== ''
            ? ': ' . trim($message)
            : '';

        return "{$prefix} {$endpoint} (status {$status}){$suffix}.";
    }

    private function safeResponse($data)
    {
        // Avoid logging huge payloads
        if (is_array($data) && count($data) > 2000) {
            return ['_truncated' => true];
        }
        return $data;
    }
}
