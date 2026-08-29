@php
    $formatSize = fn (int $mib): string => $mib >= 1024
        ? round($mib / 1024, 1).' '.__('calagopus::client.unit.gb')
        : $mib.' '.__('calagopus::client.unit.mb');

    $cpu = (int) ($server->limits['cpu'] ?? 0);

    $cards = [
        ['bi-hdd-network', __('calagopus::client.address'), $server->address() ?? __('calagopus::client.address_pending')],
        ['bi-memory', __('calagopus::client.memory'), $formatSize((int) ($server->limits['memory'] ?? 0))],
        ['bi-hdd', __('calagopus::client.disk'), $formatSize((int) ($server->limits['disk'] ?? 0))],
        ['bi-cpu', __('calagopus::client.cpu'), $cpu === 0 ? __('calagopus::client.cpu_unlimited') : __('calagopus::client.cpu_value', ['value' => round($cpu / 100, 2)])],
    ];
@endphp

@if ($server->isSuspended)
    <div role="status" class="mb-4 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        <i class="bi bi-pause-circle mt-0.5" aria-hidden="true"></i>
        <p>{{ __('calagopus::client.state.suspended') }}</p>
    </div>
@endif

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-6">
    @foreach ($cards as [$icon, $label, $value])
        <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
            <div class="p-4 md:p-5 flex justify-between gap-x-3">
                <div>
                    {{-- dark:text-gray-400 added on purpose: gray-500 alone measures 3.67:1 on slate-900, below the 4.5:1 AA floor. --}}
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">{{ $value }}</h3>
                    </div>
                </div>
                <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-600 text-white rounded-full dark:bg-indigo-900 dark:text-indigo-200">
                    <i class="bi {{ $icon }}" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-2">
    <div class="flex mt-2">
        <a href="{{ $ssoUrl }}" target="_blank" rel="noopener noreferrer" class="w-full btn-primary text-center py-2 px-4">
            {{ __('calagopus::client.open_panel') }}
            <span class="sr-only">{{ __('calagopus::client.new_window') }}</span>
        </a>
    </div>
</div>

<section class="mt-6 rounded-xl border border-gray-200 p-4 dark:border-gray-800" aria-labelledby="calagopus-retention-heading">
    <h3 id="calagopus-retention-heading" class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('calagopus::client.retention.heading') }}</h3>

    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        {{ $retentionDays > 0
            ? __('calagopus::client.retention.notice', ['days' => $retentionDays])
            : __('calagopus::client.retention.notice_unlimited') }}
    </p>

    <form method="POST" action="{{ route('calagopus.backups.preference', ['service' => $service]) }}" class="mt-3">
        @csrf
        <fieldset>
            <legend class="sr-only">{{ __('calagopus::client.retention.heading') }}</legend>

            <div class="flex items-start gap-2">
                <input type="radio" id="calagopus-keep-yes" name="keep" value="1" @checked($keepBackups) class="mt-1">
                <label for="calagopus-keep-yes" class="text-sm text-gray-800 dark:text-gray-200">
                    {{ $retentionDays > 0
                        ? __('calagopus::client.retention.keep_days', ['days' => $retentionDays])
                        : __('calagopus::client.retention.keep_unlimited') }}
                </label>
            </div>

            <div class="mt-2 flex items-start gap-2">
                <input type="radio" id="calagopus-keep-no" name="keep" value="0" @checked(! $keepBackups) class="mt-1">
                <label for="calagopus-keep-no" class="text-sm text-gray-800 dark:text-gray-200">{{ __('calagopus::client.retention.discard') }}</label>
            </div>
        </fieldset>

        <button type="submit" class="mt-3 inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
            {{ __('calagopus::client.retention.save') }}
        </button>
    </form>

    <p class="mt-3 text-sm">
        <a href="{{ route('calagopus.backups.index') }}" class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('calagopus::client.retention.manage') }}</a>
    </p>
</section>
