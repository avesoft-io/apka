<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CommaSeparatedNames implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::accepts($value)) {
            $fail('Separate each applicant name with a comma.');
        }
    }

    public static function accepts(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || preg_match('/[\r\n;]/', $value) === 1) {
            return false;
        }

        $parts = preg_split('/\s*,\s*/', $value);

        if ($parts === false) {
            return false;
        }

        foreach ($parts as $part) {
            if ($part === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    public static function names(string $value): array
    {
        $parts = preg_split('/\s*,\s*/', trim($value));

        if ($parts === false) {
            return [];
        }

        $names = [];

        foreach ($parts as $part) {
            if ($part === '') {
                return [];
            }

            $names[] = $part;
        }

        return $names;
    }
}
