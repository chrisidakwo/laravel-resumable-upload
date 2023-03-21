<?php
/**
 * Created by PhpStorm.
 * User: leodanielstuder
 * Date: 01.06.19
 * Time: 17:44
 */

namespace ChrisIdakwo\ResumableUpload\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ChrisIdakwo\ResumableUpload\Models\FileUpload;
use ChrisIdakwo\ResumableUpload\Upload\UploadService;

final class AsyncProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Only allow 1 try.
    public $tries = 1;

    public $fileUpload;
    protected $broadcastKey;

    /**
     * CompleteAndProcessUpload constructor.
     * @param FileUpload $fileUpload
     */
    public function __construct(FileUpload $fileUpload, string $broadcastKey)
    {
        $this->fileUpload = $fileUpload;
        $this->broadcastKey = $broadcastKey;
    }

    public function handle(UploadService $service)
    {
        $service->processAsync($this->fileUpload, $this->broadcastKey);
    }
}
