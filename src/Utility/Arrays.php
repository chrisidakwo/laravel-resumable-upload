<?php
declare(strict_types=1);

namespace ChrisIdakwo\ResumableUpload\Utility;

final class Arrays
{

    public static function filterNullValues(array $array): array
    {
        return array_filter($array, static fn($item) => $item !== null);
    }

}
