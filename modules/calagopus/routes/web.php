<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

use App\Modules\Calagopus\Controllers\Client\BackupController;
use App\Modules\Calagopus\Controllers\Client\SsoController;

Route::name('calagopus.')
    ->prefix('calagopus')
    ->middleware(['throttle:calagopus-sso'])
    ->group(function () {
        Route::get('/sso/{service}', [SsoController::class, 'redirect'])->name('sso');
        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::delete('/backups', [BackupController::class, 'destroy'])->name('backups.destroy');
    });
