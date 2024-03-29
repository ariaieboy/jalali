<?php

declare(strict_types=1);

namespace Ariaieboy\Jalali;

class Assertion
{
    public static function between(int $value, int $min, int $max): bool
    {
        if ($min > $value || $max < $value) {
            throw new \InvalidArgumentException('Invalid value range');
        }

        return true;
    }

    public static function greaterOrEqualThan(int $value, int $limit): bool
    {
        if ($value < $limit) {
            throw new \InvalidArgumentException('Invalid value range');
        }

        return true;
    }
}
