<?php

declare(strict_types=1);

namespace ChrisIdakwo\ResumableUpload\Utility;

use Illuminate\Support\Str;

final class Tokens
{
    private const DEFAULT_TOKEN_LENGTH = 64;

    public static function generateRandom(int $length = self::DEFAULT_TOKEN_LENGTH): string
    {
        return strtolower(Str::random($length));
    }

}
