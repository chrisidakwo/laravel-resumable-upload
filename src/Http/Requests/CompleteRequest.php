<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

final class CompleteRequest extends JsonRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $table = config('resumable-upload.table_name');

        return [
            'token' => "required|string|size:64|exists:{$table},token",
        ];
    }
}
