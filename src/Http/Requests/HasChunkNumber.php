<?php

namespace ChrisIdakwo\ResumableUpload\Http\Requests;

trait HasChunkNumber
{
    /**
     * @return string
     */
    protected function chunkNumberKey(): string
    {
        return config('resumable-upload.request_keys.chunk_number', 'resumableChunkNumber');
    }

    /**
     * @return int
     */
    public function getChunkNumber(): int
    {
        return $this->get($this->chunkNumberKey());
    }

}
