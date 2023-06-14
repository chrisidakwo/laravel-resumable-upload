<?php

namespace ChrisIdakwo\ResumableUpload\Upload;

use Exception;
use Throwable;

final class UploadProcessingException extends Exception
{
    private string|null $userMessage = null;

    private function __construct($message = "", Throwable|null $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function getUserMessage(): string|null
    {
        return $this->userMessage;
    }

    public static function with(string|null $userShowableMessage, string $internalMessage, Throwable|null $previous = null): self {
        $instance = new self($internalMessage, $previous);
        $instance->userMessage = $userShowableMessage;
        return $instance;
    }

}
