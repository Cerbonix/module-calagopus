@if ($server === null)
    <p role="status" class="rounded-lg border border-gray-300 bg-gray-50 p-4 text-gray-900 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100">
        {{ __('calagopus::admin.panel.'.$reason) }}
    </p>
@else
    <div class="space-y-4">
        @if ($server->isSuspended)
            <p role="status" class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                <i class="bi bi-pause-circle mt-0.5" aria-hidden="true"></i>
                {{ __('calagopus::admin.panel.suspended') }}
            </p>
        @endif

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.uuid') }}</dt>
                <dd class="mt-1 break-all font-mono text-sm text-gray-900 dark:text-gray-100">{{ $server->uuid }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.address') }}</dt>
                <dd class="mt-1 font-mono text-sm text-gray-900 dark:text-gray-100">{{ $server->address() ?? __('calagopus::admin.panel.no_address') }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.node') }}</dt>
                <dd class="mt-1 break-all font-mono text-sm text-gray-900 dark:text-gray-100">{{ $server->nodeUuid ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.limits') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ (int) ($server->limits['cpu'] ?? 0) }}% CPU,
                    {{ (int) ($server->limits['memory'] ?? 0) }} MiB,
                    {{ (int) ($server->limits['disk'] ?? 0) }} MiB
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.external_id') }}</dt>
                <dd class="mt-1 font-mono text-sm text-gray-900 dark:text-gray-100">{{ $server->externalId ?? '-' }}</dd>
            </div>
        </dl>

        <a href="{{ $panelUrl }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
            {{ __('calagopus::admin.panel.open') }}
            <span class="sr-only">{{ __('calagopus::admin.panel.new_window') }}</span>
        </a>
    </div>
@endif
