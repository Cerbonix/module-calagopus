@if ($unreachable)
    <p role="status" class="rounded-lg border border-gray-300 bg-gray-50 p-4 text-gray-900 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100">
        {{ __('calagopus::admin.import.no_panel') }}
    </p>
@elseif (empty($servers))
    <p role="status" class="rounded-lg border border-gray-300 bg-gray-50 p-4 text-gray-900 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100">
        {{ __('calagopus::admin.import.empty') }}
    </p>
@else
    <div class="space-y-4">
        @include('admin/shared/search-select', [
            'name' => 'calagopus_server_uuid',
            'label' => __('calagopus::admin.import.server'),
            'options' => $servers,
            'value' => null,
            'help' => __('calagopus::admin.import.server_help'),
        ])
    </div>
@endif
