@if ($server === null)
    <p role="status" class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('calagopus::admin.panel.'.$reason) }}
    </p>
@else
    @php
        $cards = [
            ['bi-fingerprint', __('calagopus::admin.panel.uuid'), $server->uuid],
            ['bi-hdd-network', __('calagopus::admin.panel.address'), $server->address() ?? __('calagopus::admin.panel.no_address')],
            ['bi-diagram-3', __('calagopus::admin.panel.node'), $server->nodeUuid ?? '-'],
            ['bi-link-45deg', __('calagopus::admin.panel.external_id'), $server->externalId ?? '-'],
        ];
    @endphp

    @if ($server->isSuspended)
        <div role="status" class="mb-4 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <i class="bi bi-pause-circle mt-0.5" aria-hidden="true"></i>
            <p>{{ __('calagopus::admin.panel.suspended') }}</p>
        </div>
    @endif

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-6">
        <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
            <div class="p-4 md:p-5 flex justify-between gap-x-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('calagopus::admin.panel.limits') }}</p>
                    <div class="mt-1 flex items-center gap-x-2">
                        <h3 class="text-xl font-medium text-gray-800 dark:text-gray-200">
                            {{ (int) ($server->limits['cpu'] ?? 0) }}% CPU
                        </h3>
                    </div>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ (int) ($server->limits['memory'] ?? 0) }} MiB, {{ (int) ($server->limits['disk'] ?? 0) }} MiB
                    </p>
                </div>
                <div class="flex-shrink-0 flex justify-center items-center w-[46px] h-[46px] bg-indigo-600 text-white rounded-full dark:bg-indigo-900 dark:text-indigo-200">
                    <i class="bi bi-cpu" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        @foreach ($cards as [$icon, $label, $value])
            <div class="flex flex-col bg-white border shadow-sm rounded-xl dark:bg-slate-900 dark:border-gray-800">
                <div class="p-4 md:p-5 flex justify-between gap-x-3">
                    <div class="min-w-0">
                        {{-- dark:text-gray-400 added on purpose: gray-500 alone measures 3.67:1 on slate-900, below the 4.5:1 AA floor. --}}
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <div class="mt-1 flex items-center gap-x-2">
                            <h3 class="break-all font-mono text-sm text-gray-800 dark:text-gray-200">{{ $value }}</h3>
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
            <a href="{{ $panelUrl }}" target="_blank" rel="noopener noreferrer" class="w-full btn-primary text-center py-2 px-4">
                {{ __('calagopus::admin.panel.open') }}
                <span class="sr-only">{{ __('calagopus::admin.panel.new_window') }}</span>
            </a>
        </div>
    </div>
@endif
