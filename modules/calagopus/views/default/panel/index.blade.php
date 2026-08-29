@php
    $formatSize = function (int $mib): string {
        return $mib >= 1024 ? round($mib / 1024, 1).' '.__('calagopus::client.unit.gb') : $mib.' '.__('calagopus::client.unit.mb');
    };
@endphp

<section aria-labelledby="calagopus-server-heading" class="space-y-6">
    <h2 id="calagopus-server-heading" class="sr-only">{{ __('calagopus::client.heading') }}</h2>

    @if ($server->isSuspended)
        <div role="status" class="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <i class="bi bi-pause-circle mt-0.5" aria-hidden="true"></i>
            <p>{{ __('calagopus::client.state.suspended') }}</p>
        </div>
    @endif

    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border bg-white p-4 dark:border-gray-800 dark:bg-slate-900">
            <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::client.address') }}</dt>
            <dd class="mt-1 text-lg font-medium text-gray-900 dark:text-gray-100">
                @if ($server->address())
                    <code>{{ $server->address() }}</code>
                @else
                    <span class="text-gray-600 dark:text-gray-400">{{ __('calagopus::client.address_pending') }}</span>
                @endif
            </dd>
        </div>

        <div class="rounded-xl border bg-white p-4 dark:border-gray-800 dark:bg-slate-900">
            <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::client.memory') }}</dt>
            <dd class="mt-1 text-lg font-medium text-gray-900 dark:text-gray-100">{{ $formatSize((int) ($server->limits['memory'] ?? 0)) }}</dd>
        </div>

        <div class="rounded-xl border bg-white p-4 dark:border-gray-800 dark:bg-slate-900">
            <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::client.disk') }}</dt>
            <dd class="mt-1 text-lg font-medium text-gray-900 dark:text-gray-100">{{ $formatSize((int) ($server->limits['disk'] ?? 0)) }}</dd>
        </div>

        <div class="rounded-xl border bg-white p-4 dark:border-gray-800 dark:bg-slate-900">
            <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::client.cpu') }}</dt>
            <dd class="mt-1 text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ (int) ($server->limits['cpu'] ?? 0) === 0 ? __('calagopus::client.cpu_unlimited') : __('calagopus::client.cpu_value', ['value' => round(((int) $server->limits['cpu']) / 100, 2)]) }}
            </dd>
        </div>
    </dl>

    <a href="{{ $panelUrl }}" target="_blank" rel="noopener noreferrer"
       {{-- indigo-600 kept in dark mode too: indigo-500 on white text measures 4.47:1, just under the 4.5:1 AA floor. --}}
       class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:focus-visible:outline-indigo-400">
        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
        {{ __('calagopus::client.open_panel') }}
        <span class="sr-only">{{ __('calagopus::client.new_window') }}</span>
    </a>
</section>
