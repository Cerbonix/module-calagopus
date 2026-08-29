<?php

/*
 * This file is part of the Calagopus provisioning module for CLIENTXCMS.
 *
 * Copyright (c) 2026 Cerbonix - https://cerbonix.net
 */

namespace App\Modules\Calagopus;

use App\Abstracts\AbstractServerType;

class CalagopusServerType extends AbstractServerType
{
    protected string $uuid = 'calagopus';

    protected string $title = 'Calagopus';
}
