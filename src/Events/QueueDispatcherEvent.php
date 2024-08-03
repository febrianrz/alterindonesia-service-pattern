<?php

namespace Alterindonesia\ServicePattern\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;

class QueueDispatcherEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $jobClass,
        public string $queue = 'default',
        public array $payload = []
    ) {
        $this->initiateIntegrationLogging();
    }

    private function initiateIntegrationLogging(): void
    {
        if(!Schema::hasTable('integration_logs')) {
            Schema::create('integration_logs', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('queue')->nullable();
                $table->json('metadata')->nullable();
                $table->string('type')->default('Push');
                $table->float('duration')->nullable();
                $table->timestamps();
            });
        }
    }
}
