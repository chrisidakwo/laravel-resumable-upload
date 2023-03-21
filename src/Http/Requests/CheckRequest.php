<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

final class CheckRequest extends JsonRequest
{
    use HasChunkNumber;

    public function rules() {
        return [
            'token' => 'required|string|size:64|exists:fileuploads,token',
            $this->chunkNumberKey() => 'required|integer|min:1',
        ];
    }



}
