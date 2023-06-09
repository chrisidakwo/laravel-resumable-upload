<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

final class InitRequest extends JsonRequest
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'handler' => 'required|string',
            'name' => 'required|string',
            'size' => 'required|integer|min:1',
            'type' => 'required|string',
            'payload' => 'nullable|array',
        ];
    }
}
