<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        @include('admin/shared/select', ['name' => 'server_id', 'label' => __('calagopus::admin.config.server'), 'options' => $servers, 'value' => $config->server_id])
    </div>
    <div class="md:col-span-2">
        @include('admin/shared/search-select', ['name' => 'egg_uuid', 'label' => __('calagopus::admin.config.egg'), 'options' => $eggs, 'value' => $config->egg_uuid, 'help' => __('calagopus::admin.config.egg_help')])
    </div>

    <div class="md:col-span-3">
        @include('admin/shared/search-select-multiple', ['name' => 'location_uuids[]', 'label' => __('calagopus::admin.config.locations'), 'options' => $locations, 'value' => $currentLocations, 'multiple' => true, 'help' => __('calagopus::admin.config.locations_help')])
    </div>

    <div>
        @include('admin/shared/input', ['name' => 'port_start', 'label' => __('calagopus::admin.config.port_start'), 'value' => $config->port_start, 'help' => __('calagopus::admin.config.port_range_help'), 'type' => 'number', 'min' => 1])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'port_end', 'label' => __('calagopus::admin.config.port_end'), 'value' => $config->port_end, 'type' => 'number', 'min' => 1])
    </div>
    <div>
        @include('admin/shared/checkbox', ['name' => 'dedicated_ip', 'label' => __('calagopus::admin.config.dedicated_ip'), 'value' => $config->dedicated_ip, 'help' => __('calagopus::admin.config.dedicated_ip_help')])
    </div>

    <div>
        @include('admin/shared/input', ['name' => 'cpu', 'label' => __('calagopus::admin.config.cpu'), 'value' => $config->cpu, 'help' => __('calagopus::admin.config.cpu_help'), 'type' => 'number', 'min' => 0])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'memory', 'label' => __('calagopus::admin.config.memory'), 'value' => $config->memory, 'help' => __('calagopus::admin.config.mib_help'), 'type' => 'number', 'min' => 0])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'disk', 'label' => __('calagopus::admin.config.disk'), 'value' => $config->disk, 'help' => __('calagopus::admin.config.mib_help'), 'type' => 'number', 'min' => 0])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'swap', 'label' => __('calagopus::admin.config.swap'), 'value' => $config->swap, 'help' => __('calagopus::admin.config.swap_help'), 'type' => 'number', 'min' => -1])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'memory_overhead', 'label' => __('calagopus::admin.config.memory_overhead'), 'value' => $config->memory_overhead, 'help' => __('calagopus::admin.config.mib_help'), 'type' => 'number', 'min' => 0])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'io_weight', 'label' => __('calagopus::admin.config.io_weight'), 'value' => $config->io_weight, 'help' => __('calagopus::admin.config.io_weight_help'), 'type' => 'number', 'min' => 0, 'optional' => true])
    </div>

    <div>
        @include('admin/shared/input', ['name' => 'allocations', 'label' => __('calagopus::admin.config.allocations'), 'value' => $config->allocations, 'help' => __('calagopus::admin.config.allocations_help'), 'type' => 'number', 'min' => 0])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'databases', 'label' => __('calagopus::admin.config.databases'), 'value' => $config->databases, 'type' => 'number', 'min' => 0])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'backups', 'label' => __('calagopus::admin.config.backups'), 'value' => $config->backups, 'type' => 'number', 'min' => 0])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'schedules', 'label' => __('calagopus::admin.config.schedules'), 'value' => $config->schedules, 'type' => 'number', 'min' => 0])
    </div>
    <div>
        @include('admin/shared/input', ['name' => 'backup_retention_days', 'label' => __('calagopus::admin.config.backup_retention_days'), 'value' => $config->backup_retention_days, 'help' => __('calagopus::admin.config.backup_retention_days_help'), 'type' => 'number', 'min' => 0])
    </div>

    <div class="md:col-span-2">
        @include('admin/shared/input', ['name' => 'image', 'label' => __('calagopus::admin.config.image'), 'value' => $config->image, 'help' => __('calagopus::admin.config.image_help')])
    </div>
    <div class="md:col-span-3">
        @include('admin/shared/textarea', ['name' => 'startup', 'label' => __('calagopus::admin.config.startup'), 'value' => $config->startup, 'help' => __('calagopus::admin.config.startup_help')])
    </div>

    <div class="md:col-span-2">
        @include('admin/shared/input', ['name' => 'server_name', 'label' => __('calagopus::admin.config.server_name'), 'value' => $config->server_name, 'help' => __('calagopus::admin.config.server_name_help'), 'optional' => true])
    </div>
    <div class="md:col-span-3">
        @include('admin/shared/input', ['name' => 'server_description', 'label' => __('calagopus::admin.config.server_description'), 'value' => $config->server_description, 'optional' => true])
    </div>

    <div>
        @include('admin/shared/checkbox', ['name' => 'start_on_completion', 'label' => __('calagopus::admin.config.start_on_completion'), 'value' => $config->start_on_completion])
    </div>
    <div>
        @include('admin/shared/checkbox', ['name' => 'skip_installer', 'label' => __('calagopus::admin.config.skip_installer'), 'value' => $config->skip_installer, 'help' => __('calagopus::admin.config.skip_installer_help')])
    </div>
</div>
