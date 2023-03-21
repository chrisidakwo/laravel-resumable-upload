<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

trait HasChunkNumber
{

    protected function chunkNumberKey(): string {
        return config('resumable-upload.request_keys.chunk_number', 'resumableChunkNumber');
    }

    public function getChunkNumber(): int {
        return $this->get($this->chunkNumberKey());
    }

}
