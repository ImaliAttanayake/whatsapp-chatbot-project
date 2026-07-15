<x-app-layout>
  @php
    $isApiTab = ($tab ?? 'api') === 'api';
    $activePaginator = $isApiTab ? $apiLogs : $errorLogs;
    $activeTotal = $activePaginator?->total() ?? 0;
    $errorStatus = $errorStatus ?? 'all';
    $errorStatusCounts = $errorStatusCounts ?? ['all' => 0, 'active' => 0, 'fixed' => 0];
    $errorActiveWindowMinutes = $errorActiveWindowMinutes ?? 30;
    $appTimezone = (string) config('app.timezone', 'UTC');
  @endphp

  <div class="max-w-7xl mx-auto p-4">
    <div class="rounded-2xl bg-white/5 border border-white/10 overflow-hidden">
      <div class="p-4 sm:p-6 border-b border-white/10 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start sm:items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-slt-primary/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-slt-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              @if($isApiTab)
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-7.938 4h15.876c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L2.34 16c-.77 1.333.192 3 1.732 3z" />
              @endif
            </svg>
          </div>
          <div>
            <h1 class="text-xl font-semibold text-white">{{ $isApiTab ? 'API Logs' : 'Error Logs' }}</h1>
            <p class="text-sm text-slt-muted">
              {{ $isApiTab ? 'SLT WhatsApp API call history' : 'Application errors from Laravel log files' }}
            </p>
          </div>
        </div>
        <div class="text-sm text-slt-muted sm:text-right">
          {{ $activeTotal }} total records
        </div>
      </div>

      <div class="p-4 border-b border-white/10 bg-white/5">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex flex-wrap rounded-xl border border-white/10 bg-slt-ink/40 p-1">
            <a href="{{ route('logs.index', ['tab' => 'api']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ $isApiTab ? 'bg-slt-primary text-white' : 'text-slt-muted hover:text-white hover:bg-white/5' }}">
              API Logs
            </a>
            <a href="{{ route('logs.index', ['tab' => 'errors']) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-all {{ !$isApiTab ? 'bg-red-500 text-white' : 'text-slt-muted hover:text-white hover:bg-white/5' }}">
              Error Logs
            </a>
          </div>

          @if(!$isApiTab)
            <div class="flex flex-wrap rounded-xl border border-white/10 bg-slt-ink/40 p-1">
              <a href="{{ route('logs.index', ['tab' => 'errors', 'error_status' => 'all']) }}"
                 class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $errorStatus === 'all' ? 'bg-white/15 text-white' : 'text-slt-muted hover:text-white hover:bg-white/5' }}">
                All ({{ $errorStatusCounts['all'] ?? 0 }})
              </a>
              <a href="{{ route('logs.index', ['tab' => 'errors', 'error_status' => 'active']) }}"
                 class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $errorStatus === 'active' ? 'bg-red-500 text-white' : 'text-slt-muted hover:text-white hover:bg-white/5' }}">
                Active ({{ $errorStatusCounts['active'] ?? 0 }})
              </a>
              <a href="{{ route('logs.index', ['tab' => 'errors', 'error_status' => 'fixed']) }}"
                 class="px-3 py-2 rounded-lg text-sm font-medium transition-all {{ $errorStatus === 'fixed' ? 'bg-slt-accent text-white' : 'text-slt-muted hover:text-white hover:bg-white/5' }}">
                Fixed ({{ $errorStatusCounts['fixed'] ?? 0 }})
              </a>
            </div>
            <span class="text-xs text-slt-muted">Active means seen in the last {{ $errorActiveWindowMinutes }} minutes and not recovered by a later successful API call.</span>
          @endif
        </div>
      </div>

      @if($isApiTab)
        @if(($apiLogs?->count() ?? 0) === 0)
          <div class="p-10 text-center text-slt-muted">No API logs available.</div>
        @else
          <div class="md:hidden divide-y divide-white/10">
            @foreach($apiLogs as $log)
              <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                  @php $logTime = $log->display_created_at ?? $log->created_at; @endphp
                  @php $result = $log->display_result ?? (((int) ($log->status ?? 0)) < 300 ? 'success' : 'failed'); @endphp
                  @php $resultLabel = $log->display_result_label ?? (string) ($log->status ?? 'N/A'); @endphp
                  <div>
                    <div class="text-white font-medium">{{ optional($logTime)->format('H:i:s') ?? 'N/A' }}</div>
                    <div class="text-xs text-slt-muted">{{ optional($logTime)->format('Y-m-d') ?? 'N/A' }} ({{ $appTimezone }})</div>
                  </div>
                  @if($result === 'success')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slt-accent/20 text-slt-accent text-xs">
                      {{ $resultLabel }}
                    </span>
                  @elseif($result === 'timeout')
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-500/20 text-orange-300 text-xs">
                      {{ $resultLabel }}
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-500/20 text-red-400 text-xs">
                      {{ $resultLabel }}
                    </span>
                  @endif
                </div>

                <div class="text-sm">
                  <span class="text-xs px-2 py-1 rounded bg-white/10 text-slt-muted mr-2">
                    {{ $log->method ?? 'POST' }}
                  </span>
                  <span class="text-slt-info font-medium break-all">{{ $log->endpoint }}</span>
                </div>

                <div class="flex items-center gap-2">
                  @php
                    $durationBarClass = $result === 'timeout'
                      ? 'bg-orange-400'
                      : (($log->duration_ms ?? 0) < 500 ? 'bg-slt-accent' : (($log->duration_ms ?? 0) < 1000 ? 'bg-yellow-500' : 'bg-red-500'));
                  @endphp
                  <div class="w-16 h-1.5 rounded-full bg-white/10 overflow-hidden">
                    <div class="h-full rounded-full {{ $durationBarClass }}"
                         style="width: {{ min(100, ($log->duration_ms ?? 0) / 10) }}%"></div>
                  </div>
                  <span class="text-slt-muted text-sm">{{ $log->duration_ms }}ms</span>
                </div>

                <details class="group">
                  <summary class="cursor-pointer text-slt-info hover:text-slt-info/80 flex items-center gap-1 text-sm">
                    <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    View Request
                  </summary>
                  <pre class="mt-3 p-4 bg-slt-ink rounded-xl text-xs text-slt-muted overflow-auto max-w-full border border-white/10">{{ json_encode($log->request, JSON_PRETTY_PRINT) }}</pre>
                </details>
              </div>
            @endforeach
          </div>

          <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
              <thead class="bg-white/5">
                <tr>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Time</th>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Endpoint</th>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Status</th>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Duration</th>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Details</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                @foreach($apiLogs as $log)
                  <tr class="hover:bg-white/5 transition-colors">
                    @php $logTime = $log->display_created_at ?? $log->created_at; @endphp
                    @php $result = $log->display_result ?? (((int) ($log->status ?? 0)) < 300 ? 'success' : 'failed'); @endphp
                    @php $resultLabel = $log->display_result_label ?? (string) ($log->status ?? 'N/A'); @endphp
                    <td class="p-4">
                      <div class="text-white font-medium">{{ optional($logTime)->format('H:i:s') ?? 'N/A' }}</div>
                      <div class="text-xs text-slt-muted">{{ optional($logTime)->format('Y-m-d') ?? 'N/A' }} ({{ $appTimezone }})</div>
                    </td>
                    <td class="p-4">
                      <span class="text-xs px-2 py-1 rounded bg-white/10 text-slt-muted mr-2">
                        {{ $log->method ?? 'POST' }}
                      </span>
                      <span class="text-slt-info font-medium">{{ $log->endpoint }}</span>
                    </td>
                    <td class="p-4">
                      @if($result === 'success')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slt-accent/20 text-slt-accent text-sm">
                          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                          </svg>
                          {{ $resultLabel }}
                        </span>
                      @elseif($result === 'timeout')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-500/20 text-orange-300 text-sm">
                          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-8V5a1 1 0 10-2 0v6a1 1 0 00.293.707l2.5 2.5a1 1 0 001.414-1.414L11 10z" clip-rule="evenodd" />
                          </svg>
                          {{ $resultLabel }}
                        </span>
                      @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-500/20 text-red-400 text-sm">
                          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                          </svg>
                          {{ $resultLabel }}
                        </span>
                      @endif
                    </td>
                    <td class="p-4">
                      @php
                        $durationBarClass = $result === 'timeout'
                          ? 'bg-orange-400'
                          : (($log->duration_ms ?? 0) < 500 ? 'bg-slt-accent' : (($log->duration_ms ?? 0) < 1000 ? 'bg-yellow-500' : 'bg-red-500'));
                      @endphp
                      <div class="flex items-center gap-2">
                        <div class="w-16 h-1.5 rounded-full bg-white/10 overflow-hidden">
                          <div class="h-full rounded-full {{ $durationBarClass }}"
                               style="width: {{ min(100, ($log->duration_ms ?? 0) / 10) }}%"></div>
                        </div>
                        <span class="text-slt-muted text-sm">{{ $log->duration_ms }}ms</span>
                      </div>
                    </td>
                    <td class="p-4">
                      <details class="group">
                        <summary class="cursor-pointer text-slt-info hover:text-slt-info/80 flex items-center gap-1 text-sm">
                          <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                          </svg>
                          View Request
                        </summary>
                        <pre class="mt-3 p-4 bg-slt-ink rounded-xl text-xs text-slt-muted overflow-auto max-w-lg border border-white/10">{{ json_encode($log->request, JSON_PRETTY_PRINT) }}</pre>
                      </details>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      @else
        @if(($errorLogs?->count() ?? 0) === 0)
          <div class="p-10 text-center text-slt-muted">No error logs found in Laravel log files.</div>
        @else
          <div class="md:hidden divide-y divide-white/10">
            @foreach($errorLogs as $error)
              <div class="p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    @if(!empty($error['timestamp']))
                      <div class="text-white font-medium">{{ $error['timestamp']->format('H:i:s') }}</div>
                      <div class="text-xs text-slt-muted">{{ $error['timestamp']->format('Y-m-d') }}</div>
                    @else
                      <div class="text-white font-medium">{{ $error['timestamp_raw'] ?? 'Unknown' }}</div>
                    @endif
                  </div>
                  <div class="flex flex-col items-end gap-1">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-500/20 text-red-400 text-xs font-medium">
                      {{ $error['level'] ?? 'ERROR' }}
                    </span>
                    @if(($error['status'] ?? 'active') === 'fixed')
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slt-accent/20 text-slt-accent text-xs font-medium">
                        Fixed
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-500/20 text-red-400 text-xs font-medium">
                        Active
                      </span>
                    @endif
                  </div>
                </div>

                <div>
                  <div class="text-white font-medium whitespace-pre-wrap break-words">{{ $error['message'] ?? '' }}</div>
                  <div class="text-xs text-slt-muted mt-1 break-all">{{ $error['source_file'] ?? 'laravel.log' }}</div>
                </div>

                @if(!empty($error['details']))
                  <details class="group">
                    <summary class="cursor-pointer text-slt-info hover:text-slt-info/80 flex items-center gap-1 text-sm">
                      <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                      View Stack Trace
                    </summary>
                    <pre class="mt-3 p-4 bg-slt-ink rounded-xl text-xs text-slt-muted overflow-auto max-w-full border border-white/10 whitespace-pre-wrap">{{ $error['details'] }}</pre>
                  </details>
                @endif
              </div>
            @endforeach
          </div>

          <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
              <thead class="bg-white/5">
                <tr>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Time</th>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Level</th>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Status</th>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Message</th>
                  <th class="p-4 text-left text-xs font-medium text-slt-muted uppercase tracking-wider">Details</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                @foreach($errorLogs as $error)
                  <tr class="hover:bg-white/5 transition-colors align-top">
                    <td class="p-4 whitespace-nowrap">
                      @if(!empty($error['timestamp']))
                        <div class="text-white font-medium">{{ $error['timestamp']->format('H:i:s') }}</div>
                        <div class="text-xs text-slt-muted">{{ $error['timestamp']->format('Y-m-d') }}</div>
                      @else
                        <div class="text-white font-medium">{{ $error['timestamp_raw'] ?? 'Unknown' }}</div>
                      @endif
                    </td>
                    <td class="p-4 whitespace-nowrap">
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-500/20 text-red-400 text-sm font-medium">
                        {{ $error['level'] ?? 'ERROR' }}
                      </span>
                    </td>
                    <td class="p-4 whitespace-nowrap">
                      @if(($error['status'] ?? 'active') === 'fixed')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slt-accent/20 text-slt-accent text-sm font-medium">
                          Fixed
                        </span>
                      @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-500/20 text-red-400 text-sm font-medium">
                          Active
                        </span>
                      @endif
                    </td>
                    <td class="p-4">
                      <div class="text-white font-medium whitespace-pre-wrap">{{ $error['message'] ?? '' }}</div>
                      <div class="text-xs text-slt-muted mt-1">
                        {{ $error['source_file'] ?? 'laravel.log' }}
                      </div>
                    </td>
                    <td class="p-4">
                      @if(!empty($error['details']))
                        <details class="group">
                          <summary class="cursor-pointer text-slt-info hover:text-slt-info/80 flex items-center gap-1 text-sm">
                            <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            View Stack Trace
                          </summary>
                          <pre class="mt-3 p-4 bg-slt-ink rounded-xl text-xs text-slt-muted overflow-auto max-w-4xl border border-white/10 whitespace-pre-wrap">{{ $error['details'] }}</pre>
                        </details>
                      @else
                        <span class="text-slt-muted text-sm">No extra details</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      @endif

      <div class="p-4 border-t border-white/10">
        {{ $activePaginator?->links('pagination.slt') }}
      </div>
    </div>
  </div>

  <footer class="border-t border-white/10 py-8 mt-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <img src="{{ asset('images/slt-logo.png') }}" alt="SLT" class="h-8 w-auto" />
          <span class="text-slt-muted text-sm">SLT WhatsApp Business </span>
        </div>
        <div class="text-slt-muted text-sm text-center sm:text-right">
          &copy; {{ date('Y') }} SLT-Digital platform. All rights reserved.
        </div>
      </div>
    </div>
  </footer>
</x-app-layout>
