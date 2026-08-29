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
            $table->unsignedInteger('backup_retention_days')->default(30)->after('backups');
        });

        // A backup outlives its server but loses every link to it, so the pairing is recorded while the server still exists.
        Schema::create('calagopus_backup_purges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->uuid('node_uuid');
            $table->uuid('backup_uuid')->unique();
            $table->string('backup_name')->nullable();
            $table->timestamp('purge_at')->index();
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
        });
    }

    public static function blueprint(Blueprint $blueprint): string
    {
        return 'create_calagopus_backup_purges_table';
    }

    public function down(): void
    {
        Schema::dropIfExists('calagopus_backup_purges');

        Schema::table('calagopus_configs', function (Blueprint $table) {
            $table->dropColumn('backup_retention_days');
        });
    }
};
