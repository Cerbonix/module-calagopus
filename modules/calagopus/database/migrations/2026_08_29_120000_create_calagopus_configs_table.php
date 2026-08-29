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
        Schema::create('calagopus_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('server_id');

            $table->uuid('egg_uuid');
            $table->json('location_uuids');

            // Panel units: cpu is a percentage of one core (100 = 1 vCPU), the rest are MiB.
            $table->integer('cpu')->default(100);
            $table->integer('memory')->default(1024);
            $table->integer('memory_overhead')->default(0);
            $table->integer('swap')->default(0);
            $table->integer('disk')->default(5120);
            $table->integer('io_weight')->nullable();

            $table->integer('allocations')->default(1);
            $table->integer('databases')->default(0);
            $table->integer('backups')->default(0);
            $table->integer('schedules')->default(0);

            $table->string('image');
            $table->text('startup');
            $table->string('server_name')->nullable();
            $table->string('server_description')->nullable();
            $table->boolean('start_on_completion')->default(true);
            $table->boolean('skip_installer')->default(false);

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public static function blueprint(Blueprint $blueprint): string
    {
        return 'create_calagopus_configs_table';
    }

    public function down(): void
    {
        Schema::dropIfExists('calagopus_configs');
    }
};
