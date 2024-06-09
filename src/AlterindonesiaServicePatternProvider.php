<?php

namespace Alterindonesia\ServicePattern;

use Alterindonesia\ServicePattern\Console\CreateServiceCommand;
use Illuminate\Support\ServiceProvider;

class AlterindonesiaServicePatternProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->commands([
            CreateServiceCommand::class
        ]);
    }
}
