<?php

namespace ChrisIdakwo\ResumableUpload\Upload;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use ChrisIdakwo\ResumableUpload\Contracts\UploadHandler;
use ChrisIdakwo\ResumableUpload\Http\Requests\InitRequest;
use ChrisIdakwo\ResumableUpload\Jobs\AsyncProcessingJob;
use ChrisIdakwo\ResumableUpload\Models\FileUpload;
use ChrisIdakwo\ResumableUpload\Utility\Files;
use ChrisIdakwo\ResumableUpload\Utility\Resources;
use ChrisIdakwo\ResumableUpload\Utility\Tokens;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class UploadService
{
    /**
     * @throws InvalidChunksException
     */
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

    /**
     * @return array<string, mixed>
     */
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

    /**
     * @throws Exception
     */
    private function combineChunks(FileUpload $fileUpload): string
    {
        $combinedFile = Files::tmp();
        $chunks = $this->getChunks($fileUpload);

        Resources::combine($chunks, $combinedFile);
        Files::deleteExisting(...$chunks);

        return $combinedFile;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws UploadProcessingException|Exception
     */
    private function process(UploadHandler $handler, FileUpload $fileUpload): array
    {
        $uploadedFile = new SplFileInfo($this->combineChunks($fileUpload));

        try {
            $handler->validateUploadedFile($uploadedFile, $fileUpload);
            $response = $handler->handle($uploadedFile, $fileUpload);
        } catch (Exception $exception) {
            Files::deleteExisting($uploadedFile);
            throw $exception;
        }

        Files::deleteExisting($uploadedFile);
        return $response ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function dispatchAsync(FileUpload $fileUpload): array
    {
        $broadcastingKey = sprintf('upload-%s-%d', Tokens::generateRandom(16), $fileUpload->getKey());
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

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed>|null $rules
     *
     * @throws ValidationException
     */
    private function validatedArray(array $payload, array|null $rules): array
    {
        return $rules
            ? Validator::make($payload, $rules)->validate()
            : [];
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed>|null $payloadRules
     *
     * @throws ValidationException
     */
    private function createFileUpload(array $attributes, array|null $payloadRules): FileUpload
    {
        return new FileUpload(
            [
                'name' => basename($attributes['name']),
                'size' => (int)$attributes['size'],
                'type' => $attributes['type'],
                'extension' => Files::getExtension($attributes['name']),
                'chunks' => $this->getChunkNumber($attributes['size']),
                'payload' => $this->validatedArray($attributes['payload'] ?? [], $payloadRules),
            ]
        );
    }

    /**
     * @throws Throwable
     */
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

    /**
     * @throws RuntimeException
     */
    public function uploadChunk(FileUpload $fileUpload, int $chunkNumber, UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new RuntimeException('Invalid file');
        }

        Files::writeChunk($file->getRealPath(), $fileUpload->token, $chunkNumber);
    }

    /**
     * @throws InvalidChunksException|Throwable|UploadProcessingException|BindingResolutionException
     */
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

    /**
     * @throws Exception
     */
    public function processAsync(FileUpload $fileUpload, string $broadcastKey): void
    {
        /** @var UploadHandler $handler */
        $handler = app()->make($fileUpload->handler);

        try {
            $response = $this->process($handler, $fileUpload);
            $handler->broadcastProcessedAsync($fileUpload, $broadcastKey, $response);
        } catch (Exception $exception) {
            $handler->broadcastFailedAsyncProcessing($fileUpload, $broadcastKey, $exception);
            throw $exception;
        }
    }

}
