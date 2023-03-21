<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadRequest extends FormRequest
{
    use HasChunkNumber;

    public function rules(): array
    {
        $table = config('resumable-upload.table_name');

        return [
            'token' => "required|string|size:64|exists:$table,token",
            $this->chunkNumberKey() => 'required|integer|min:1',
            'file' => 'required|file|max:' . config('resumable-upload.chunk_size'),
        ];
    }
}
