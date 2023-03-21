<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

final class CompleteRequest extends JsonRequest
{
    public function rules() {
        return [
            'token' => 'required|string|size:64|exists:fileuploads,token',
        ];
    }
}
