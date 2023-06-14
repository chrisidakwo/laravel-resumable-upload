<?php

namespace ChrisIdakwo\ResumableUpload\Contracts;

use Exception;
use Illuminate\Http\Request;
use ChrisIdakwo\ResumableUpload\Models\FileUpload;
use ChrisIdakwo\ResumableUpload\Upload\UploadProcessingException;
use SplFileInfo;

abstract class UploadHandler
{
    /**
     * Return the middleware to load on the init call.
     * By default, no middleware are applied.
     * @return array|null
     */
    abstract public function middleware(): array|null;

    /**
     * Validation rules for the payload
     */
    abstract public function payloadRules(): array|null;

    /**
     * Perform some other checks after the validation is done.
     * This is also the place where you can add attributes to the payload.
     * You can use {$fileUpload->appendToPayload($key, $value)} for that.
     */
    public function afterPayloadValidation(FileUpload $fileUpload, Request $request): void
    {
    }

    /**
     * Hook called as soon as the file has been combined successfully. This is a good place to
     * do some further checks like size, mime type or a virus scan.
     */
    public function validateUploadedFile(SplFileInfo $file, FileUpload $fileUpload): void
    {
    }

    /**
     * Bool if the file should be processed async
     *
     * @return bool
     */
    public function supportsAsyncProcessing(): bool
    {
        return false;
    }

    /**
     * Process the uploaded file
     * During the handling you may throw {@see UploadProcessingException} using
     * UploadProcessingException::with('user message', 'internal message') to return messages
     * to the client.
     *
     * @return null|array<string, mixed>
     * @throws UploadProcessingException
     */
    abstract public function handle(SplFileInfo $file, FileUpload $fileUpload): array|null;

    /**
     * Inform the client of a failure to process the File asynchronously.
     * Note, the exceptions are not filtered here. All exceptions are passed to the client.
     *
     * @param FileUpload $fileUpload
     * @param string $broadcastKey
     * @param Exception $exception
     */
    public function broadcastFailedAsyncProcessing(
        FileUpload $fileUpload,
        string $broadcastKey,
        Exception $exception
    ): void {
    }

    /**
     * Inform the client that the processing of the file with the key was done.
     *
     * @param FileUpload $fileUpload
     * @param string $broadcastKey
     * @param array|null $processedData
     */
    public function broadcastProcessedAsync(FileUpload $fileUpload, string $broadcastKey, array|null $processedData): void
    {
    }
}
