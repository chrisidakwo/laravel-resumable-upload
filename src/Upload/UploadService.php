<?php

namespace ChrisIdakwo\ResumableUpload\Upload;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use ChrisIdakwo\ResumableUpload\Contracts\UploadHandler;
use ChrisIdakwo\ResumableUpload\Http\Requests\InitRequest;
use ChrisIdakwo\ResumableUpload\Jobs\AsyncProcessingJob;
use ChrisIdakwo\ResumableUpload\Models\FileUpload;
use ChrisIdakwo\ResumableUpload\Utility\Files;
use ChrisIdakwo\ResumableUpload\Utility\Resources;
use ChrisIdakwo\ResumableUpload\Utility\Tokens;
use RuntimeException;
use SplFileInfo;

final class UploadService
{
    private function verifyChunks(FileUpload $fileUpload): void
    {
        $fileSize = 0;
        foreach ($this->getChunks($fileUpload) as $chunkFilePath) {
            if (!file_exists($chunkFilePath)) {
                throw new InvalidChunksException('Chunk file could not be located.');
            }
            $fileSize += filesize($chunkFilePath);
        }

        // Verify combined file size
        if ($fileSize !== $fileUpload->size) {
            throw new InvalidChunksException('File size does not match size given in the init call.');
        }
    }

    private function getChunks(FileUpload $fileUpload): array
    {
        $chunks = [];
        $currentChunk = 1;
        while ($currentChunk <= $fileUpload->chunks) {
            $chunks[] = Files::computeChunkFileName($fileUpload->token, $currentChunk);
            $currentChunk++;
        }

        return $chunks;
    }

    private function shouldProcessAsync(UploadHandler $handler, FileUpload $fileUpload): bool
    {
        return
            config('resumable-upload.async', false)
            && (
                $handler->supportsAsyncProcessing() || $fileUpload->size > (100 * 1024 * 1024)
            );
    }

    private function combineChunks(FileUpload $fileUpload): string
    {
        $combinedFile = Files::tmp();
        $chunks = $this->getChunks($fileUpload);

        Resources::combine($chunks, $combinedFile);
        Files::deleteExisting(...$chunks);

        return $combinedFile;
    }

    private function process(UploadHandler $handler, FileUpload $fileUpload): array
    {
        $uploadedFile = new SplFileInfo($this->combineChunks($fileUpload));

        try {
            $handler->validateUploadedFile($uploadedFile, $fileUpload);
            $response = $handler->handle($uploadedFile, $fileUpload);
        } catch (\Exception $exception) {
            Files::deleteExisting($uploadedFile);
            throw $exception;
        }

        Files::deleteExisting($uploadedFile);
        return $response ?? [];
    }

    private function dispatchAsync(FileUpload $fileUpload): array
    {
        $broadcastingKey = sprintf('upload-%s-%d', Tokens::generateRandom(16), $fileUpload->id);
        dispatch(new AsyncProcessingJob($fileUpload, $broadcastingKey))
            ->onQueue(config('resumable-upload.queue'));
        return [
            'async' => true,
            'broadcasting_key' => $broadcastingKey,
        ];
    }

    private function getChunkNumber(int $fileSize): int
    {
        return ceil($fileSize / config('resumable-upload.chunk_size'));
    }

    private function validatedArray(array $payload, ?array $rules): array
    {
        return $rules
            ? Validator::make($payload, $rules)->validate()
            : [];
    }

    private function createFileUpload(array $attributes, ?array $payloadRules): FileUpload
    {
        return new FileUpload(
            [
                'name' => basename($attributes['name']),
                'size' => (int)$attributes['size'],
                'type' => $attributes['type'],
                'extension' => Files::getExtension($attributes['name']),
                'chunks' => $this->getChunkNumber($attributes['size']),
                'payload' => $this->validatedArray($attributes['payload'] ?? null, $payloadRules),
            ]
        );
    }

    public function init(UploadHandler $handler, InitRequest $request): FileUpload
    {
        $fileUpload = $this->createFileUpload($request->validated(), $handler->payloadRules());

        // Perform additional steps after the validation is complete.
        // This allows you to store some more data in the Payload or manipulate the FileUpload itself
        $handler->afterPayloadValidation($fileUpload, $request);

        $fileUpload->token = Tokens::generateRandom();
        $fileUpload->handler = get_class($handler);
        $fileUpload->saveOrFail();
        return $fileUpload;
    }

    public function uploadChunk(FileUpload $fileUpload, int $chunkNumber, UploadedFile $file)
    {
        if (!$file->isValid()) {
            throw new RuntimeException('Invalid file');
        }
        Files::writeChunk($file->getRealPath(), $fileUpload->token, $chunkNumber);
    }

    public function completeUpload(FileUpload $fileUpload): array
    {
        // First we mark the file as completed as we expect all chunks to be present now.
        $fileUpload->is_complete = true;
        $fileUpload->saveOrFail();

        // Ensure all expected chunks are present and chunk filesize matches
        $this->verifyChunks($fileUpload);

        /** @var UploadHandler $handler */
        $handler = app()->make($fileUpload->handler);
        return $this->shouldProcessAsync($handler, $fileUpload)
            ? $this->dispatchAsync($fileUpload)
            : $this->process($handler, $fileUpload);
    }

    public function processAsync(FileUpload $fileUpload, string $broadcastKey): void
    {
        /** @var UploadHandler $handler */
        $handler = app()->make($fileUpload->handler);

        try {
            $response = $this->process($handler, $fileUpload);
            $handler->broadcastProcessedAsync($fileUpload, $broadcastKey, $response);
        } catch (\Exception $exception) {
            $handler->broadcastFailedAsyncProcessing($fileUpload, $broadcastKey, $exception);
            throw $exception;
        }
    }

}
