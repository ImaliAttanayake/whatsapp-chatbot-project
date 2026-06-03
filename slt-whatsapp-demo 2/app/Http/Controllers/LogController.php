<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogController extends Controller
{
    private const ERROR_ACTIVE_WINDOW_MINUTES = 5;
    private const API_TIMEOUT_MS = 20000;

    public function index(Request $request)
    {
        // Simple rule: only admins can view logs (set is_admin=1 for your user)
        abort_unless(auth()->user()?->is_admin, 403);

        $tab = $request->query('tab', 'api');
        if (!in_array($tab, ['api', 'errors'], true)) {
            $tab = 'api';
        }

        $apiLogs = null;
        $errorLogs = null;
        $errorStatus = 'all';
        $errorStatusCounts = ['all' => 0, 'active' => 0, 'fixed' => 0];
        $errorActiveWindowMinutes = max(
            1,
            (int) config('chat.error_active_window_minutes', self::ERROR_ACTIVE_WINDOW_MINUTES)
        );

        if ($tab === 'errors') {
            $errorStatus = $request->query('error_status', 'all');
            if (!in_array($errorStatus, ['all', 'active', 'fixed'], true)) {
                $errorStatus = 'all';
            }

            $entries = $this->collectErrorEntries();
            [$entries, $errorStatusCounts] = $this->annotateErrorStatus(
                $entries,
                $errorActiveWindowMinutes
            );

            if ($errorStatus !== 'all') {
                $entries = array_values(array_filter($entries, function (array $entry) use ($errorStatus): bool {
                    return ($entry['status'] ?? 'active') === $errorStatus;
                }));
            }

            $page = max(1, (int) $request->query('page', 1));
            $perPage = 30;
            $offset = ($page - 1) * $perPage;

            $errorLogs = new LengthAwarePaginator(
                array_slice($entries, $offset, $perPage),
                count($entries),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $appTz = (string) config('app.timezone', 'UTC');
            $apiLogs = ApiLog::latest()
                ->paginate(30)
                ->withQueryString()
                ->through(function (ApiLog $log) use ($appTz): ApiLog {
                    $createdAt = $log->created_at?->copy()->setTimezone($appTz);
                    $log->setAttribute('display_created_at', $createdAt);
                    $analysis = $this->analyzeApiLogStatus($log);
                    $log->setAttribute('display_result', $analysis['result']);
                    $log->setAttribute('display_result_label', $analysis['label']);
                    return $log;
                });
        }

        return view('logs.index', compact(
            'tab',
            'apiLogs',
            'errorLogs',
            'errorStatus',
            'errorStatusCounts',
            'errorActiveWindowMinutes'
        ));
    }

    /**
     * Read Laravel log files and return all error-level entries.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectErrorEntries(): array
    {
        $logDir = storage_path('logs');
        if (!File::isDirectory($logDir)) {
            return [];
        }

        $files = collect(File::files($logDir))
            ->filter(function (\SplFileInfo $file): bool {
                $name = $file->getFilename();
                return Str::startsWith($name, 'laravel') && Str::endsWith($name, '.log');
            })
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->values();

        $entries = [];
        $levels = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
        $pattern = '/^\[(?<time>[^\]]+)\]\s+[^\s]+\.(?<level>[A-Z]+):\s(?<message>.*)$/';

        foreach ($files as $file) {
            $lines = @file($file->getPathname(), FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                continue;
            }

            $current = null;

            foreach ($lines as $line) {
                if (preg_match($pattern, $line, $matches) === 1) {
                    if ($current !== null) {
                        $entries[] = $current;
                    }

                    $level = strtoupper($matches['level'] ?? '');
                    if (!in_array($level, $levels, true)) {
                        $current = null;
                        continue;
                    }

                    $current = [
                        'timestamp' => $this->parseLogTimestamp((string) ($matches['time'] ?? '')),
                        'timestamp_raw' => (string) ($matches['time'] ?? ''),
                        'level' => $level,
                        'message' => trim((string) ($matches['message'] ?? '')),
                        'details' => [],
                        'source_file' => $file->getFilename(),
                    ];

                    continue;
                }

                if ($current !== null) {
                    $current['details'][] = $line;
                }
            }

            if ($current !== null) {
                $entries[] = $current;
            }
        }

        usort($entries, function (array $a, array $b): int {
            $at = $a['timestamp'] instanceof Carbon ? $a['timestamp']->getTimestamp() : 0;
            $bt = $b['timestamp'] instanceof Carbon ? $b['timestamp']->getTimestamp() : 0;
            if ($at === $bt) {
                return strcmp((string) ($b['source_file'] ?? ''), (string) ($a['source_file'] ?? ''));
            }

            return $bt <=> $at;
        });

        return array_map(function (array $entry): array {
            $entry['details'] = trim(implode("\n", $entry['details']));
            return $entry;
        }, $entries);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array{0: array<int, array<string, mixed>>, 1: array{all: int, active: int, fixed: int}}
     */
    private function annotateErrorStatus(array $entries, int $activeWindowMinutes): array
    {
        $activeThresholdTs = now()->subMinutes(max(1, $activeWindowMinutes))->getTimestamp();
        $lastSeenBySignature = [];
        $lastEndpointBySignature = [];
        $latestSuccessfulEndpointTs = $this->latestSuccessfulApiEndpointTimestamps();
        $latestSuccessfulApiTs = $this->latestSuccessfulApiTimestamp();

        foreach ($entries as $entry) {
            $signature = $this->buildErrorSignature($entry);
            $ts = $entry['timestamp'] instanceof Carbon ? $entry['timestamp']->getTimestamp() : 0;
            $endpoint = $this->extractApiEndpointFromErrorEntry($entry);

            if (!isset($lastSeenBySignature[$signature]) || $ts > $lastSeenBySignature[$signature]) {
                $lastSeenBySignature[$signature] = $ts;
                $lastEndpointBySignature[$signature] = $endpoint;
            } elseif (
                $ts === ($lastSeenBySignature[$signature] ?? 0)
                && empty($lastEndpointBySignature[$signature])
                && $endpoint !== null
            ) {
                $lastEndpointBySignature[$signature] = $endpoint;
            }
        }

        $counts = ['all' => 0, 'active' => 0, 'fixed' => 0];
        foreach ($entries as &$entry) {
            $signature = $this->buildErrorSignature($entry);
            $lastSeenTs = $lastSeenBySignature[$signature] ?? 0;
            $status = $lastSeenTs >= $activeThresholdTs ? 'active' : 'fixed';

            if ($status === 'active') {
                $endpoint = $lastEndpointBySignature[$signature] ?? null;
                $latestSuccessTs = ($endpoint !== null)
                    ? ($latestSuccessfulEndpointTs[$endpoint] ?? 0)
                    : 0;

                // If API recovered after the latest error for this signature, mark as fixed immediately.
                if ($latestSuccessTs > $lastSeenTs) {
                    $status = 'fixed';
                } elseif (
                    $this->isSltApiEndpoint($endpoint)
                    && $latestSuccessfulApiTs > $lastSeenTs
                ) {
                    // Fallback: if any SLT endpoint succeeded after this error, treat it as recovered.
                    $status = 'fixed';
                }
            }

            $entry['signature'] = $signature;
            $entry['status'] = $status;
            $entry['endpoint_hint'] = $lastEndpointBySignature[$signature] ?? null;

            $counts['all']++;
            $counts[$status]++;
        }
        unset($entry);

        return [$entries, $counts];
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function buildErrorSignature(array $entry): string
    {
        $level = strtoupper((string) ($entry['level'] ?? 'ERROR'));
        $message = strtolower((string) ($entry['message'] ?? ''));

        // Normalize variable values so repeated issues group together.
        $message = preg_replace('/https?:\/\/\S+/', '<url>', $message) ?? $message;
        $message = preg_replace('/\b\d+\b/', '<n>', $message) ?? $message;
        $message = preg_replace('/\s+/', ' ', trim($message)) ?? trim($message);

        return $level . '|' . $message;
    }

    /**
     * @return array<string, int>
     */
    private function latestSuccessfulApiEndpointTimestamps(): array
    {
        $rows = ApiLog::query()
            ->select(['endpoint', 'status', 'response', 'created_at'])
            ->latest('created_at')
            ->limit(5000)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $endpoint = strtolower(trim((string) ($row->endpoint ?? '')));
            if ($endpoint === '' || isset($map[$endpoint])) {
                continue;
            }

            $analysis = $this->analyzeApiLogStatus($row);
            if (($analysis['result'] ?? 'failed') !== 'success') {
                continue;
            }

            $ts = $row->created_at instanceof Carbon ? $row->created_at->getTimestamp() : 0;
            if ($ts > 0) {
                $map[$endpoint] = $ts;
            }
        }

        return $map;
    }

    private function latestSuccessfulApiTimestamp(): int
    {
        $latest = ApiLog::query()
            ->latest('created_at')
            ->limit(2000)
            ->get()
            ->first(function (ApiLog $row): bool {
                $analysis = $this->analyzeApiLogStatus($row);
                return ($analysis['result'] ?? 'failed') === 'success';
            });

        if (!$latest || !$latest->created_at instanceof Carbon) {
            return 0;
        }

        return $latest->created_at->getTimestamp();
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function extractApiEndpointFromErrorEntry(array $entry): ?string
    {
        $message = (string) ($entry['message'] ?? '');
        if ($message === '') {
            return null;
        }

        $lower = strtolower($message);
        $eventToEndpoint = [
            'messages.fetch_failed' => '/getmessages.php',
            'messages.inbound_uuid_failed' => '/getmessages.php',
            'messages.reply_failed' => '/reply.php',
        ];

        foreach ($eventToEndpoint as $event => $endpoint) {
            if (Str::contains($lower, $event)) {
                return $endpoint;
            }
        }

        if (preg_match('#https?://[^\s"]+(/[^?\s"]+)#i', $message, $matches) === 1) {
            $path = strtolower((string) ($matches[1] ?? ''));
            return $this->isSltApiEndpoint($path) ? $path : null;
        }

        return null;
    }

    private function isSltApiEndpoint(?string $endpoint): bool
    {
        $normalized = strtolower(trim((string) $endpoint));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, [
            '/login.php',
            '/getmessages.php',
            '/getrecentactivemobiles.php',
            '/reply.php',
        ], true);
    }

    private function parseLogTimestamp(string $timestamp): ?Carbon
    {
        try {
            $tz = (string) config('app.timezone', 'UTC');
            $raw = trim($timestamp);
            if ($raw === '') {
                return null;
            }
            $hasOffset = (bool) preg_match('/(Z|[+\-]\d{2}:?\d{2})$/i', $raw);
            $parsed = $hasOffset ? Carbon::parse($raw) : Carbon::parse($raw, $tz);
            return $parsed->setTimezone($tz);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{result: string, label: string}
     */
    private function analyzeApiLogStatus(ApiLog $log): array
    {
        $statusCode = (int) ($log->status ?? 0);
        $durationMs = (int) ($log->duration_ms ?? 0);
        $response = $log->response;

        $responseStatus = is_array($response) ? ($response['status'] ?? null) : null;
        $responseError = is_array($response) ? ($response['error'] ?? null) : null;
        $responseMessage = is_array($response) ? ($response['message'] ?? null) : null;

        $statusText = is_string($responseStatus) ? trim($responseStatus) : '';
        $errorText = is_string($responseError) ? trim($responseError) : '';
        $messageText = is_string($responseMessage) ? trim($responseMessage) : '';
        $nestedErrorText = '';
        if (is_array($responseError)) {
            $nestedErrorText = trim((string) ($responseError['message'] ?? ''));
        }

        $haystack = strtolower(implode(' | ', array_filter([
            $statusText,
            $errorText,
            $messageText,
            $nestedErrorText,
            json_encode($response),
        ])));

        $isTimeoutPayload = Str::contains($haystack, [
            'timeout',
            'timed out',
            'time out',
            'curl error 28',
            'operation timed out',
            'deadline exceeded',
        ]);

        $isTimeoutByDuration = $durationMs >= self::API_TIMEOUT_MS;

        if ($isTimeoutPayload || $isTimeoutByDuration) {
            return ['result' => 'timeout', 'label' => 'Timeout'];
        }

        $hasPayloadError = $statusText !== '' && Str::startsWith(strtolower($statusText), 'error');
        $hasPayloadError = $hasPayloadError || $errorText !== '' || $nestedErrorText !== '';

        if ($statusCode >= 400 || $hasPayloadError) {
            return [
                'result' => 'failed',
                'label' => $statusCode > 0 ? (string) $statusCode : 'Error',
            ];
        }

        return [
            'result' => 'success',
            'label' => $statusCode > 0 ? (string) $statusCode : 'OK',
        ];
    }
}
