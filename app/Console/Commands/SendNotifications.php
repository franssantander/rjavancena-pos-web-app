<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\HttpFoundation\Request;
use App\Http\Controllers\NotificationController;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for low and moderate stock levels';
    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Create a request object
        $request = Request::create('/notifications/store', 'POST');

        // Resolve the NotificationController from the container
        $controller = app(NotificationController::class);
        $response = $controller->store($request);

        // Output the result to the console
        $this->info($response->getContent());
    }
}
