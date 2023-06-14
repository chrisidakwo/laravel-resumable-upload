<?php

namespace ChrisIdakwo\ResumableUpload\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use ChrisIdakwo\ResumableUpload\Utility\Arrays;
use Illuminate\Http\JsonResponse;

final class ApiResponse implements Responsable
{
    private array $response;
    private int $statusCode;

    private function __construct(array $response, int $statusCode = 200)
    {
        $this->response = $response;
        $this->statusCode = $statusCode;
    }

    /**
     * @param array|Collection|Model|null $data
     * @return static
     */
    public static function successful(Model|Collection|array|null $data = null): self
    {
        return new self(
            [
                'success' => true,
                'data' => $data,
            ]
        );
    }

    public static function error(string $message, int $statusCode): self
    {
        return new self(
            [
                'success' => false,
                'error' => $message,
            ],
            $statusCode
        );
    }

    public function toResponse($request): JsonResponse
    {
        return response()
            ->json(
                Arrays::filterNullValues($this->response),
                $this->statusCode
            );
    }
}
