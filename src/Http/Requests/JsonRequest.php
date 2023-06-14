<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class JsonRequest extends FormRequest
{
    /**
     * @return array<string, mixed>|null
     */
    final public function validationData(): array|null
    {
        return $this->isJson() ? $this->json()->all() : $this->all();
    }
}
