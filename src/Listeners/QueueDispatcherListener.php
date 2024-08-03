<?php

namespace App\ServicePackage\Listeners;

use Alterindonesia\ServicePattern\Events\QueueDispatcherEvent;
use Illuminate\Support\Facades\Queue;

class QueueDispatcherListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(QueueDispatcherEvent $event): void
    {
        $startTime = microtime(true);
        Queue::push(
            $event->jobClass,
            $event->payload,
            $event->queue
        );
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        \DB::table('integration_logs')->insert([
            'name' => $event->jobClass,
            'queue' => $event->queue,
            'type' => 'Push',
            'metadata' => json_encode($event->payload),
            'duration' => $executionTime,
        ]);
    }
}
