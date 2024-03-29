<?php

declare(strict_types=1);

namespace Ariaieboy\Jalali;

use Carbon\Carbon;
use DateTimeZone;

class Jalali implements JalaliInterface
{
    use Converter;

    public function __construct(
        private int $year,
        private int $month,
        private int $day,
        private int $hour = 0,
        private int $minute = 0,
        private int $second = 0,
        private ?DateTimeZone $timezone = null
    ) {

        Assertion::between($year, 1000, 3000);
        Assertion::between($month, 1, 12);
        Assertion::between($day, 1, 31);

        if ($month > 6) {
            Assertion::between($day, 1, 30);
        }

        if (! CalendarUtils::isLeapJalaliYear($year) && $month === 12) {
            Assertion::between($day, 1, 29);
        }
        Assertion::between($hour, 0, 24);
        Assertion::between($minute, 0, 59);
        Assertion::between($second, 0, 59);
    }

    public static function now(?DateTimeZone $timeZone = null): Jalali
    {
        return static::fromCarbon(Carbon::now($timeZone));
    }

    public static function fromCarbon(Carbon $carbon): Jalali
    {
        $jDate = CalendarUtils::toJalali($carbon->year, $carbon->month, $carbon->day);

        return new static(
            $jDate[0],
            $jDate[1],
            $jDate[2],
            $carbon->hour,
            $carbon->minute,
            $carbon->second,
            $carbon->getTimezone()
        );
    }

    public static function fromFormat(string $format, string $timestamp, ?DateTimeZone $timeZone = null): Jalali
    {
        return static::fromCarbon(CalendarUtils::createCarbonFromFormat($format, $timestamp, $timeZone));
    }

    public static function forge(string|\DateTimeInterface $timestamp, ?DateTimeZone $timeZone = null): Jalali
    {
        return static::fromDateTime($timestamp, $timeZone);
    }

    public static function fromDateTime(string|\DateTimeInterface $dateTime, ?DateTimeZone $timeZone = null): Jalali
    {
        if (is_numeric($dateTime)) {
            return static::fromCarbon(Carbon::createFromTimestamp($dateTime, $timeZone));
        }

        return static::fromCarbon(new Carbon($dateTime, $timeZone));
    }

    public function getFirstDayOfWeek(): Jalali
    {
        return (new static(
            $this->getYear(),
            $this->getMonth(),
            $this->getDay(),
            $this->getHour(),
            $this->getMinute(),
            $this->getSecond(),
            $this->getTimezone()
        ))->subDays($this->getDayOfWeek());
    }

    public function getFirstDayOfMonth(): Jalali
    {
        return new static(
            $this->getYear(),
            $this->getMonth(),
            1,
            $this->getHour(),
            $this->getMinute(),
            $this->getSecond(),
            $this->getTimezone()
        );
    }

    public function getFirstDayOfYear(): Jalali
    {
        return new static(
            $this->getYear(),
            1,
            1,
            $this->getHour(),
            $this->getMinute(),
            $this->getSecond(),
            $this->getTimezone()
        );
    }

    public function getMonthDays(): int
    {
        if ($this->getMonth() <= 6) {
            return 31;
        }

        if ($this->getMonth() < 12 || $this->isLeapYear()) {
            return 30;
        }

        return 29;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function isLeapYear(): bool
    {
        return CalendarUtils::isLeapJalaliYear($this->getYear());
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function subMonths(int $months = 1): Jalali
    {
        Assertion::greaterOrEqualThan($months, 1);

        $diff = ($this->getMonth() - $months);

        if ($diff >= 1) {
            $day = $this->getDay();
            $targetMonthDays = $this->getDaysOf($diff);
            $targetDay = min($day, $targetMonthDays);

            return new static(
                $this->getYear(),
                $diff,
                $targetDay,
                $this->getHour(),
                $this->getMinute(),
                $this->getSecond(),
                $this->getTimezone()
            );
        }

        $years = abs((int) ($diff / 12));
        $date = $years > 0 ? $this->subYears($years) : clone $this;
        $diff = 12 - abs($diff % 12) - $date->getMonth();

        return $diff > 0 ? $date->subYears(1)->addMonths($diff) : $date->subYears(1);
    }

    public function getDay(): int
    {
        return $this->day;
    }

    public function getDaysOf(int $monthNumber = 1): int
    {
        Assertion::between($monthNumber, 1, 12);

        $months = [
            1 => 31,
            2 => 31,
            3 => 31,
            4 => 31,
            5 => 31,
            6 => 31,
            7 => 30,
            8 => 30,
            9 => 30,
            10 => 30,
            11 => 30,
            12 => $this->isLeapYear() ? 30 : 29,
        ];

        return $months[$monthNumber];
    }

    public function getHour(): int
    {
        return $this->hour;
    }

    public function getMinute(): int
    {
        return $this->minute;
    }

    public function getSecond(): int
    {
        return $this->second;
    }

    public function getTimezone(): ?DateTimeZone
    {
        return $this->timezone;
    }

    public function subYears(int $years = 1): Jalali
    {
        Assertion::greaterOrEqualThan($years, 1);

        return new static(
            $this->getYear() - $years,
            $this->getMonth(),
            $this->getDay(),
            $this->getHour(),
            $this->getMinute(),
            $this->getSecond(),
            $this->getTimezone()
        );
    }

    public function addMonths(int $months = 1): Jalali
    {
        Assertion::greaterOrEqualThan($months, 1);

        $years = (int) ($months / 12);
        $months = ($months % 12);
        $date = $years > 0 ? $this->addYears($years) : clone $this;

        while ($months > 0) {
            $nextMonth = ($date->getMonth() + 1) % 12;
            $nextMonthDays = $date->getDaysOf($nextMonth === 0 ? 12 : $nextMonth);
            $nextMonthDay = min($date->getDay(), $nextMonthDays);

            $days = ($date->getMonthDays() - $date->getDay()) + $nextMonthDay;

            $date = $date->addDays($days);
            $months--;
        }

        return $date;
    }

    public function addYears(int $years = 1): Jalali
    {
        Assertion::greaterOrEqualThan($years, 1);

        $year = $this->getYear() + $years;
        if (CalendarUtils::isLeapJalaliYear($year) === false && $this->getMonth() === 12 && $this->getDay() === $this->getDaysOf(12)) {
            $day = 29;
        } else {
            $day = $this->getDay();
        }

        return new static(
            $year,
            $this->getMonth(),
            $day,
            $this->getHour(),
            $this->getMinute(),
            $this->getSecond(),
            $this->getTimezone()
        );
    }

    public function subDays(int $days = 1): Jalali
    {
        return static::fromCarbon($this->toCarbon()->subDays($days));
    }

    public function subDay(): Jalali
    {
        return $this->subDays(1);
    }

    public function toCarbon(): Carbon
    {
        $gDate = CalendarUtils::toGregorian($this->getYear(), $this->getMonth(), $this->getDay());
        $carbon = Carbon::createFromDate($gDate[0], $gDate[1], $gDate[2], $this->getTimezone());

        $carbon->setTime($this->getHour(), $this->getMinute(), $this->getSecond());

        return $carbon;
    }

    public function addHours(int $hours = 1): Jalali
    {
        return static::fromCarbon($this->toCarbon()->addHours($hours));
    }

    public function subHours(int $hours = 1): Jalali
    {
        return static::fromCarbon($this->toCarbon()->subHours($hours));
    }

    public function addMinutes(int $minutes = 1): Jalali
    {
        return static::fromCarbon($this->toCarbon()->addMinutes($minutes));
    }

    public function subMinutes(int $minutes = 1): Jalali
    {
        return static::fromCarbon($this->toCarbon()->subMinutes($minutes));
    }

    public function addSeconds(int $secs = 1): Jalali
    {
        return static::fromCarbon($this->toCarbon()->addSeconds($secs));
    }

    public function subSeconds(int $secs = 1): Jalali
    {
        return static::fromCarbon($this->toCarbon()->subSeconds($secs));
    }

    public function equalsTo(Jalali $other): bool
    {
        return $this->equalsToCarbon($other->toCarbon());
    }

    public function equalsToCarbon(Carbon $carbon): bool
    {
        return $this->toCarbon()->equalTo($carbon);
    }

    public function greaterThan(Jalali $other): bool
    {
        return $this->greaterThanCarbon($other->toCarbon());
    }

    public function greaterThanCarbon(Carbon $carbon): bool
    {
        return $this->toCarbon()->greaterThan($carbon);
    }

    public function lessThan(Jalali $other): bool
    {
        return $this->lessThanCarbon($other->toCarbon());
    }

    public function lessThanCarbon(Carbon $carbon): bool
    {
        return $this->toCarbon()->lessThan($carbon);
    }

    public function greaterThanOrEqualsTo(Jalali $other): bool
    {
        return $this->greaterThanOrEqualsToCarbon($other->toCarbon());
    }

    public function greaterThanOrEqualsToCarbon(Carbon $carbon): bool
    {
        return $this->toCarbon()->greaterThanOrEqualTo($carbon);
    }

    public function lessThanOrEqualsTo(Jalali $other): bool
    {
        return $this->lessThanOrEqualsToCarbon($other->toCarbon());
    }

    public function lessThanOrEqualsToCarbon(Carbon $carbon): bool
    {
        return $this->toCarbon()->lessThanOrEqualTo($carbon);
    }

    public function isStartOfWeek(): bool
    {
        return $this->isSaturday();
    }

    public function isSaturday(): bool
    {
        return $this->isDayOfWeek(Carbon::SATURDAY);
    }

    public function isDayOfWeek(int $day): bool
    {
        Assertion::between($day, 0, 6);

        return $this->toCarbon()->isDayOfWeek($day);
    }

    public function isEndOfWeek(): bool
    {
        return $this->isFriday();
    }

    public function isFriday(): bool
    {
        return $this->isDayOfWeek(Carbon::FRIDAY);
    }

    public function isToday(): bool
    {
        return $this->toCarbon()->isToday();
    }

    public function isTomorrow(): bool
    {
        return $this->toCarbon()->isTomorrow();
    }

    public function isYesterday(): bool
    {
        return $this->toCarbon()->isYesterday();
    }

    public function isFuture(): bool
    {
        return $this->toCarbon()->isFuture();
    }

    public function isPast(): bool
    {
        return $this->toCarbon()->isPast();
    }

    /**
     * @return array{
     * 'year' : int,
     * 'month' : int,
     * 'day' : int,
     * 'dayOfWeek' : int,
     * 'dayOfYear' : int,
     * 'hour' : int,
     * 'minute' : int,
     * 'second' : int,
     * 'micro' : int,
     * 'timestamp' : float|int|string,
     * 'formatted' : string,
     * 'timezone' : DateTimeZone|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'dayOfWeek' => $this->getDayOfWeek(),
            'dayOfYear' => $this->getDayOfYear(),
            'hour' => $this->hour,
            'minute' => $this->minute,
            'second' => $this->second,
            'micro' => $this->toCarbon()->micro,
            'timestamp' => $this->toCarbon()->timestamp,
            'formatted' => $this->toString(),
            'timezone' => $this->timezone,
        ];
    }

    public function getDayOfWeek(): int
    {
        if ($this->isSaturday()) {
            return 0;
        }

        if ($this->isSunday()) {
            return 1;
        }

        if ($this->isMonday()) {
            return 2;
        }

        if ($this->isTuesday()) {
            return 3;
        }

        if ($this->isWednesday()) {
            return 4;
        }

        if ($this->isThursday()) {
            return 5;
        }

        return 6;
    }

    public function isSunday(): bool
    {
        return $this->isDayOfWeek(Carbon::SUNDAY);
    }

    public function isMonday(): bool
    {
        return $this->isDayOfWeek(Carbon::MONDAY);
    }

    public function isTuesday(): bool
    {
        return $this->isDayOfWeek(Carbon::TUESDAY);
    }

    public function isWednesday(): bool
    {
        return $this->isDayOfWeek(Carbon::WEDNESDAY);
    }

    public function isThursday(): bool
    {
        return $this->isDayOfWeek(Carbon::THURSDAY);
    }

    public function getDayOfYear(): int
    {
        $dayOfYear = 0;
        for ($m = 1; $m < $this->getMonth(); $m++) {
            if ($m <= 6) {
                $dayOfYear += 31;

                continue;
            }

            if ($m < 12) {
                $dayOfYear += 30;

                continue;
            }
        }

        return $dayOfYear + $this->getDay();
    }

    public function toString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function format(string $format): string
    {
        return CalendarUtils::strftime($format, $this->toCarbon());
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function ago(): string
    {
        $now = time();
        $time = $this->getTimestamp();

        // build period and length arrays
        $periods = ['ثانیه', 'دقیقه', 'ساعت', 'روز', 'هفته', 'ماه', 'سال', 'قرن'];
        $lengths = [60, 60, 24, 7, 4.35, 12, 10];

        // get difference
        $difference = $now - $time;

        // set descriptor
        if ($difference < 0) {
            $difference = abs($difference); // absolute value
            $negative = true;
        }
        $j = 0;
        // do math
        for ($j; $difference >= $lengths[$j] and $j < count($lengths) - 1; $j++) {
            $difference /= $lengths[$j];
        }

        // round difference
        $difference = intval(round($difference));

        // return
        return number_format($difference).' '.$periods[$j].' '.(isset($negative) ? '' : 'پیش');
    }

    public function getTimestamp(): int
    {
        return $this->toCarbon()->getTimestamp();
    }

    public function getNextWeek(): Jalali
    {
        return $this->addDays(7);
    }

    public function getLastWeek(): Jalali
    {
        return $this->subDays(7);
    }

    public function addDays(int $days = 1): Jalali
    {
        return static::fromCarbon($this->toCarbon()->addDays($days));
    }

    public function addDay(): Jalali
    {
        return $this->addDays(1);
    }

    public function getNextMonth(): Jalali
    {
        return $this->addMonths(1);
    }

    public function getLastMonth(): Jalali
    {
        return $this->subMonths(1);
    }

    public function getWeekOfMonth(): int
    {
        return (int) floor(($this->day + 5 - $this->getDayOfWeek()) / 7) + 1;
    }
}
