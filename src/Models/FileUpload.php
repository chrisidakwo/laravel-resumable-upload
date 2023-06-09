<?php

namespace ChrisIdakwo\ResumableUpload\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Class FileUpload
 * @package ChrisIdakwo\ResumableUpload\Models
 *
 * @property integer $size
 * @property integer $chunks
 * @property string $name
 * @property string $extension
 * @property string $type
 * @property string $token
 * @property string $handler
 * @property array $payload
 * @property boolean $is_complete
 */
class FileUpload extends Model
{
    protected $fillable = ['size', 'chunks', 'name', 'extension', 'type', 'payload'];

    protected $casts = [
        'payload' => 'array',
        'is_complete' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        $this->table = config('resumable-upload.table_name');

        parent::__construct($attributes);
    }

    /**
     * @param string $key
     * @param $value
     */
    public function appendToPayload(string $key, $value): void
    {
        $payload = $this->payload;

        Arr::set($payload, $key, $value);

        $this->payload = $payload;
    }
}
