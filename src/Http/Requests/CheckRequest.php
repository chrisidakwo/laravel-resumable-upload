<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

final class CheckRequest extends JsonRequest
{
    use HasChunkNumber;

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $table = config('resumable-upload.table_name');

        return [
            'token' => "required|string|size:64|exists:{$table},token",
            $this->chunkNumberKey() => 'required|integer|min:1',
        ];
    }



}
