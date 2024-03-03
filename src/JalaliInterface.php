<?php
declare(strict_types=1);
namespace Ariaieboy\Jalali;

interface JalaliInterface
{
    public function __construct(int            $year,
                                int            $month,
                                int            $day,
                                int            $hour = 0,
                                int            $minute = 0,
                                int            $second = 0,
                                ?\DateTimeZone $timezone = null);
}