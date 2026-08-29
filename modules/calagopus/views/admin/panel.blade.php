<div class="card">
    <div class="card-heading">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
            {{ __('calagopus::admin.panel.title') }}
        </h3>
    </div>

    <div class="card-body">
        @if ($server === null)
            <p role="status" class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('calagopus::admin.panel.'.$reason) }}
            </p>
        @else
            @if ($server->isSuspended)
                <p role="status" class="mb-4 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                    <i class="bi bi-pause-circle" aria-hidden="true"></i>
                    {{ __('calagopus::admin.panel.suspended') }}
                </p>
            @endif

            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.uuid') }}</dt>
                    <dd class="mt-1 break-all font-mono text-sm text-gray-800 dark:text-gray-200">{{ $server->uuid }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.address') }}</dt>
                    <dd class="mt-1 font-mono text-sm text-gray-800 dark:text-gray-200">{{ $server->address() ?? __('calagopus::admin.panel.no_address') }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.node') }}</dt>
                    <dd class="mt-1 break-all font-mono text-sm text-gray-800 dark:text-gray-200">{{ $server->nodeUuid ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.limits') }}</dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-gray-200">
                        {{ (int) ($server->limits['cpu'] ?? 0) }}% CPU, {{ (int) ($server->limits['memory'] ?? 0) }} MiB, {{ (int) ($server->limits['disk'] ?? 0) }} MiB
                    </dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400">{{ __('calagopus::admin.panel.external_id') }}</dt>
                    <dd class="mt-1 font-mono text-sm text-gray-800 dark:text-gray-200">{{ $server->externalId ?? '-' }}</dd>
                </div>
            </dl>

            <a href="{{ $panelUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary mt-4">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                {{ __('calagopus::admin.panel.open') }}
                <span class="sr-only">{{ __('calagopus::admin.panel.new_window') }}</span>
            </a>
        @endif
    </div>
</div>
