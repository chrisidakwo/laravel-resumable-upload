<?php

namespace le0daniel\Laravel\ResumableJs\Http\Requests;

trait InteractsWithChunkNumer
{

    protected function chunkNumberKey(): string {
        return config('resumablejs.request_keys.chunk_number', 'resumableChunkNumber');
    }

    public function getChunkNumber(): int {
        return $this->get($this->chunkNumberKey());
    }

}
