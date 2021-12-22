<?php

namespace App\Jobs;

use App\Models\Tour;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TourQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $queue;

    private string $jobId;
    private array $jobData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $jobId, array $jobData)
    {
        $this->queue = 'tour-queue';
        $this->jobId = $jobId;
        $this->jobData = $jobData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Tour::create(
                [
                    'operator_id' => $this->jobData['operatorId'],
                    'external_tour_id' => $this->jobData['id'],
                    'name' => $this->jobData['name'],
                    'description' => $this->jobData['description'],
                    'price' => $this->jobData['price']
                ]
            );
        } catch (Throwable $exception) {
            Log::error('Failed tour creation', $this->jobData);
        }
    }

    public function failed(Throwable $exception)
    {
        Log::error('Failed job', [
            'jobId' => $this->jobId,
            'exception' => $exception->getMessage(),
            'jobData' => $this->jobData
        ]);
    }
}
