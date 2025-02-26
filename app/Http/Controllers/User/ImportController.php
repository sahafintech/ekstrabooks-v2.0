<?php

namespace App\Http\Controllers\User;

use App\Helper\Reply;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Bus\Batch;

class ImportController extends Controller
{
    /**
     * Get import progress percentage
     * @param string $name Queue name
     * @param string $id Batch ID
     * @return mixed
     */
    public function getImportProgress($name, $id)
    {
        try {
            // Get the batch
            $batch = Bus::findBatch($id);
            
            // Initialize variables
            $progress = 0;
            $failedJobs = 0;
            $processedJobs = 0;
            $totalJobs = 0;

            if ($batch) {
                // Get batch statistics
                $failedJobs = $batch->failedJobs;
                $totalJobs = $batch->totalJobs;
                $processedJobs = $batch->processedJobs();
                
                // Calculate progress
                if ($totalJobs > 0) {
                    $progress = round((($processedJobs + $failedJobs) / $totalJobs) * 100, 2);
                }

                // If batch is not finished, process more jobs
                if (!$batch->finished()) {
                    // Get max jobs to process
                    $maxJobs = (int)(ini_get('max_execution_time') / 10);
                    $maxJobs = max(1, $maxJobs); // At least process 1 job
                    
                    // Process jobs
                    Artisan::call('queue:work', [
                        '--once' => true,
                        '--queue' => $name,
                        '--max-jobs' => $maxJobs,
                        '--stop-when-empty' => true
                    ]);
                }
            }

            return Reply::dataOnly([
                'progress' => $progress,
                'failedJobs' => $failedJobs,
                'processedJobs' => $processedJobs,
                'totalJobs' => $totalJobs,
                'finished' => $batch ? $batch->finished() : false,
                'cancelled' => $batch ? $batch->cancelled() : false
            ]);
        } catch (\Exception $e) {
            return Reply::error('Error processing import: ' . $e->getMessage());
        }
    }

    /**
     * Get failed job exceptions for a queue
     * @param string $name Queue name
     * @return mixed
     */
    public function getQueueException($name)
    {
        try {
            $exceptions = DB::table('failed_jobs')
                ->where('queue', $name)
                ->orderBy('failed_at', 'desc')
                ->get();

            return Reply::dataOnly([
                'exceptions' => $exceptions,
                'count' => $exceptions->count()
            ]);
        } catch (\Exception $e) {
            return Reply::error('Error getting exceptions: ' . $e->getMessage());
        }
    }
}
