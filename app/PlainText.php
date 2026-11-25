<?php

namespace App;

final class PlainText
{
    public static function clean(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return trim(strip_tags(self::withoutNullBytes($value)));
    }

    public static function withoutNullBytes(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return str_replace("\0", '', $value);
    }
}
