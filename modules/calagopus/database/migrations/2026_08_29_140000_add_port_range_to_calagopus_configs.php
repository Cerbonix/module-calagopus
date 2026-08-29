<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calagopus_configs', function (Blueprint $table) {
            // Without a port range the panel assigns no allocation at all, and the server ships unreachable.
            $table->unsignedInteger('port_start')->default(25565)->after('location_uuids');
            $table->unsignedInteger('port_end')->default(25595)->after('port_start');
            $table->boolean('dedicated_ip')->default(false)->after('port_end');
        });
    }

    public static function blueprint(Blueprint $blueprint): string
    {
        return 'add_port_range_to_calagopus_configs';
    }

    public function down(): void
    {
        Schema::table('calagopus_configs', function (Blueprint $table) {
            $table->dropColumn(['port_start', 'port_end', 'dedicated_ip']);
        });
    }
};
