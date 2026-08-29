<?php

namespace App\Modules\Calagopus;

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
    }

    protected function productsTypes(): array
    {
        return [
            CalagopusProductType::class,
        ];
    }
}
