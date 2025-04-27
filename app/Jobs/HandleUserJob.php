<?php

namespace App\Jobs;

use App\Models\UserDetail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class HandleUserJob implements ShouldQueue
{
    use Queueable;

    public string $userId;

    public $queue = 'user-deleted';

    /**
     * Create a new job instance.
     */
    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $queue = $this->job->getQueue();

        try {
            if ($queue === 'user-deleted') {
                $user = UserDetail::find($this->userId);
                if (!$user) {
                    // log to rabbitmq not found
                    Log::error('User not found', ['user_id' => $this->userId]);
                }

                $user->delete();
                Log::info('User deleted', ['user_id' => $this->userId]);
            }
        } catch
        (\Exception $e) {
            Log::critical('Failed to handle job', ['error' => $e->getMessage()]);
        }
    }

}
