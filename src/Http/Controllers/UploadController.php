<?php

namespace ChrisIdakwo\ResumableUpload\Http\Controllers;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use ChrisIdakwo\ResumableUpload\Contracts\UploadHandler;
use ChrisIdakwo\ResumableUpload\Http\Requests\CompleteRequest;
use ChrisIdakwo\ResumableUpload\Http\Requests\InitRequest;
use ChrisIdakwo\ResumableUpload\Http\Requests\UploadRequest;
use ChrisIdakwo\ResumableUpload\Http\Responses\ApiResponse;
use ChrisIdakwo\ResumableUpload\Models\FileUpload;
use ChrisIdakwo\ResumableUpload\Upload\InvalidChunksException;
use ChrisIdakwo\ResumableUpload\Upload\UploadProcessingException;
use ChrisIdakwo\ResumableUpload\Upload\UploadService;
use RuntimeException;
use Throwable;

final class UploadController extends BaseController
{
    use ValidatesRequests;

    private UploadHandler $handler;
    private const INIT_REQUEST_NAME = 'init';

    public function __construct(Request $request)
    {
        $this->verifyHasHandlers();
        if ($this->isInitCall($request)) {
            $this->setHandler($request->get('handler', ''));
            $this->applyHandlerMiddleware();
        }
    }

    private function verifyHasHandlers(): void
    {
        if (empty(config('resumable-upload.handlers', false))) {
            throw new RuntimeException('No upload handlers (resumable-upload.handlers) defined in your config.');
        }
    }

    private function isInitCall(Request $request): bool
    {
        return str_contains($request->route()->getName(), self::INIT_REQUEST_NAME);
    }

    private function applyHandlerMiddleware(): void
    {
        if ($middleware = $this->handler->middleware()) {
            $this->middleware($middleware);
        }
    }

    private function setHandler(string $handlerName): void
    {
        $handlers = config('resumable-upload.handlers');

        if (!array_key_exists($handlerName, $handlers)) {
            abort(404, 'Handler not found.');
        }

        $this->handler = App::make($handlers[$handlerName]);
    }

    private function getUncompletedFileUpload(string $token): FileUpload|null
    {
        /** @var FileUpload|null $fileUpload */
        $fileUpload = FileUpload::query()->where('token', '=', $token)
            ->where('is_complete', '=', 0)
            ->firstOrFail();

        return $fileUpload;
    }

    /**
     * @throws Throwable
     */
    public function init(InitRequest $request, UploadService $manager): ApiResponse
    {
        try {
            return ApiResponse::successful(['token' => $manager->init($this->handler, $request)->token]);
        } catch (Exception $exception) {
            Log::debug('Validation failed.', compact('exception'));

            return ApiResponse::error('Input validation failed', 422);
        }
    }

    public function upload(UploadRequest $request, UploadService $manager): Response
    {
        $attributes = $request->validated();

        $manager->uploadChunk(
            $this->getUncompletedFileUpload($attributes['token']),
            $request->getChunkNumber(),
            $request->file('file')
        );

        return response('', 200);
    }

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    public function complete(CompleteRequest $request, UploadService $manager): ApiResponse
    {
        $attributes = $request->validated();

        try {
            return ApiResponse::successful(
                $manager->completeUpload($this->getUncompletedFileUpload($attributes['token']))
            );
        } catch (InvalidChunksException) {
            return ApiResponse::error('Could not locate the chunks. Did you upload all chunk files?', 422);
        } catch (UploadProcessingException $exception) {
            return ApiResponse::error($exception->getUserMessage() ?? 'Internal Error', 422);
        }
    }

}
