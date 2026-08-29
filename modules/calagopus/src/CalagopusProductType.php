<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Abstracts\AbstractProductType;
use App\Contracts\Provisioning\ServerTypeInterface;
use App\Contracts\Store\ProductConfigInterface;

class CalagopusProductType extends AbstractProductType
{
    protected string $title = 'Calagopus';

    protected string $uuid = 'calagopus';

    protected string $type = self::SERVICE;

    public function server(): ?ServerTypeInterface
    {
        return new CalagopusServerType;
    }

    public function config(): ?ProductConfigInterface
    {
        return new CalagopusConfig;
    }
}
