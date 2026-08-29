<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Modules\Calagopus\Commands\PurgeExpiredBackups;
use Illuminate\Console\Scheduling\Schedule;

class CalagopusServiceProvider extends \App\Extensions\BaseModuleServiceProvider
{
    protected string $name = 'Calagopus';

    protected string $version = '0.1.0';

    protected string $uuid = 'calagopus';

    public function boot(): void
    {
        $this->loadViews();
        $this->loadTranslations();
        $this->loadMigrations();
        $this->registerProductTypes();

        if ($this->app->runningInConsole()) {
            $this->commands([PurgeExpiredBackups::class]);
        }

        $this->registerSchedule();
    }

    public function schedule(Schedule $schedule): void
    {
        $schedule->command(PurgeExpiredBackups::class)->dailyAt('04:15')->withoutOverlapping();
    }

    protected function productsTypes(): array
    {
        return [
            CalagopusProductType::class,
        ];
    }
}
