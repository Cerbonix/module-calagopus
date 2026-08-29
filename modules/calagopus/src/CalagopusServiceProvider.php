<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Modules\Calagopus\Commands\ConfigureSso;
use App\Modules\Calagopus\Commands\PurgeExpiredBackups;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use RateLimiter;

class CalagopusServiceProvider extends \App\Extensions\BaseModuleServiceProvider
{
    protected string $name = 'Calagopus';

    protected string $version = '0.1.0';

    protected string $uuid = 'calagopus';

    public function boot(): void
    {
        RateLimiter::for('calagopus-sso', function ($request) {
            return Limit::perMinute(10)->by(optional($request->user())->id ?: $request->ip());
        });

        $this->loadViews();
        $this->loadTranslations();
        $this->loadMigrations();
        $this->registerProductTypes();

        \Route::middleware('web')->group(module_path('calagopus', 'routes/web.php'));

        // The core blanks the panel as soon as a service leaves the active state, so kept backups would have no way back.
        \View::composer('front/provisioning/services/show', InactiveServicePanel::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PurgeExpiredBackups::class, ConfigureSso::class]);
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
