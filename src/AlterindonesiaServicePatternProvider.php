<?php

namespace Alterindonesia\ServicePattern;

use Alterindonesia\ServicePattern\Console\CreateServiceCommand;
use Alterindonesia\ServicePattern\Events\QueueDispatcherEvent;
use App\ServicePackage\Listeners\QueueDispatcherListener;
use Illuminate\Support\Facades\Event;
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

        Event::listen(
            QueueDispatcherEvent::class,
            QueueDispatcherListener::class
        );
    }
}
