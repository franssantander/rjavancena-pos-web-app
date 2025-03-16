<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Begin transaction
        DB::beginTransaction();
        Log::info("DATA");
        Log::info($this->data);

        try {
            // Resolve the helper instance from the application container
            $helper = App::make(\App\Helper\Helper::class);

            // Log the details
            $log_result = $helper->log(
                $this->data['request'],
                [
                    'user_device' => $this->data['request']->eu_device,
                    'user_id' => $this->data['user']->user_id,
                    'is_history' => 0,
                    'user_action' => 'UPDATE CUSTOMER NAME',
                ],
                $this->data['result_update_logs_old_new'],
                2,
                env('PATH_FILE_INVENTORY')
            );

            // Check the log result status
            if ($log_result->getStatusCode() !== Response::HTTP_OK) {
                // Failed to create log
                DB::rollBack(); // Rollback transaction
                // Handle the error or throw an exception
                throw new \Exception('Failed to create log.');
            }

            // Commit transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of error
            DB::rollBack();
            // Log the exception
            Log::error('Job failed: ' . $e->getMessage());
            // Optionally, you can rethrow the exception or handle it as needed
        }
    }
}
