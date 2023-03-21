<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadRequest extends FormRequest
{
    use HasChunkNumber;

    public function rules()
    {
        return [
            'token' => 'required|string|size:64|exists:fileuploads,token',
            $this->chunkNumberKey() => 'required|integer|min:1',
            'file' => 'required|file|max:' . config('resumablejs.chunk_size'),
        ];
    }

}
