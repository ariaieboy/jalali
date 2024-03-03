<?php
declare(strict_types=1);

namespace Ariaieboy\Jalali;

use Carbon\Carbon;
use Exception;

/**
 * Class jDateTime
 */
class CalendarUtils
{
    public const IRANIAN_MONTHS_NAME = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public const AFGHAN_MONTHS_NAME = ['حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت'];

    /**
     * @var string[]
     */
    private static array $monthNames = self::IRANIAN_MONTHS_NAME;
    /**
     * @var string[]
     */
    private static array $temp;

    public static function useAfghanMonthsName(): void
    {
        self::$monthNames = self::AFGHAN_MONTHS_NAME;
    }

    public static function useIranianMonthsName(): void
    {
        self::$monthNames = self::IRANIAN_MONTHS_NAME;
    }

    /**
     * Converts a Gregorian date to Jalali.
     *
     * @return array{0:int,1:int,2:int}
     *               0: Year
     *               1: Month
     *               2: Day
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        return self::d2j(self::g2d($gy, $gm, $gd));
    }

    /**
     * Converts a Jalali date to Gregorian.
     *
     * @return array{0:int,1:int,2:int}
     *               0: Year
     *               1: Month
     *               2: Day
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        return self::d2g(self::j2d($jy, $jm, $jd));
    }

    /**
     * Converts a Jalali date to Gregorian.
     */
    public static function toGregorianDate(int $jy, int $jm, int $jd): \DateTime
    {
        $georgianDateArr = self::toGregorian($jy, $jm, $jd);
        $year = $georgianDateArr[0];
        $month = $georgianDateArr[1];
        $day = $georgianDateArr[2];
        $georgianDate = new \DateTime();
        $georgianDate->setDate($year, $month, $day);

        return $georgianDate;
    }

    /**
     * Checks whether a jalali date is valid or not.
     */
    public static function isValidateJalaliDate(int $jy, int $jm, int $jd): bool
    {
        return $jy >= -61 && $jy <= 3177
            && $jm >= 1 && $jm <= 12
            && $jd >= 1 && $jd <= self::jalaliMonthLength($jy, $jm);
    }

    /**
     * Checks whether a date is valid or not.
     */
    public static function checkDate(int $year, int $month, int $day, bool $isJalali = true): bool
    {
        return $isJalali === true ? self::isValidateJalaliDate($year, $month, $day) : checkdate($month, $day, $year);
    }

    /**
     *  Is this a leap year or not?
     */
    public static function isLeapJalaliYear(int $jy): bool
    {
        return self::jalaliCal($jy)['leap'] === 0;
    }

    /**
     * Number of days in a given month in a Jalali year.
     */
    public static function jalaliMonthLength(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }

        if ($jm <= 11) {
            return 30;
        }

        return self::isLeapJalaliYear($jy) ? 30 : 29;
    }

    /**
     * This function determines if the jalali (Persian) year is
     * leap (366-day long) or is the common year (365 days), and
     * finds the day in March (Gregorian calendar) of the first
     * day of the jalali year (jy).
     *
     * @param int $jy jalali calendar year (-61 to 3177)
     * @return array{'leap':int,'gy':int,'march':int}
     *               leap: number of years since the last leap year (0 to 4)
     *               gy: Gregorian year of the beginning of jalali year
     *               march: the March day of Farvardin the 1st (1st day of jy)
     *
     * @see: http://www.astro.uni.torun.pl/~kb/Papers/EMP/PersianC-EMP.htm
     *
     * @see: http://www.fourmilab.ch/documents/calendar/
     */
    public static function jalaliCal(int $jy): array
    {
        $breaks = [
            -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
        ];

        $breaksCount = count($breaks);

        $gy = $jy + 621;
        $leapJ = -14;
        $jp = $breaks[0];

        if ($jy < $jp || $jy >= $breaks[$breaksCount - 1]) {
            throw new \InvalidArgumentException('Invalid Jalali year : ' . $jy);
        }

        $jump = 0;

        for ($i = 1; $i < $breaksCount; $i += 1) {
            $jm = $breaks[$i];
            $jump = $jm - $jp;

            if ($jy < $jm) {
                break;
            }

            $leapJ = $leapJ + self::div($jump, 33) * 8 + self::div(self::mod($jump, 33), 4);

            $jp = $jm;
        }

        $n = $jy - $jp;

        $leapJ = $leapJ + self::div($n, 33) * 8 + self::div(self::mod($n, 33) + 3, 4);

        if (self::mod($jump, 33) === 4 && $jump - $n === 4) {
            $leapJ += 1;
        }

        $leapG = self::div($gy, 4) - self::div((self::div($gy, 100) + 1) * 3, 4) - 150;

        $march = 20 + $leapJ - $leapG;

        if ($jump - $n < 6) {
            $n = $n - $jump + self::div($jump + 4, 33) * 33;
        }

        $leap = self::mod(self::mod($n + 1, 33) - 1, 4);

        if ($leap === -1) {
            $leap = 4;
        }

        return [
            'leap' => $leap,
            'gy' => $gy,
            'march' => $march,
        ];
    }

    public static function div(int $a, int $b): int
    {
        return intdiv($a, $b);
    }

    public static function mod(int $a, int $b): int
    {
        return $a - intdiv($a, $b) * $b;
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    public static function d2g(int $jdn): array
    {
        $j = 4 * $jdn + 139361631;
        $j += self::div(self::div(4 * $jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
        $i = self::div(self::mod($j, 1461), 4) * 5 + 308;

        $gd = self::div(self::mod($i, 153), 5) + 1;
        $gm = self::mod(self::div($i, 153), 12) + 1;
        $gy = self::div($j, 1461) - 100100 + self::div(8 - $gm, 6);

        return [$gy, $gm, $gd];
    }

    /**
     * Calculates the Julian Day number from Gregorian or Julian
     * calendar dates. This integer number corresponds to the noon of
     * the date (i.e. 12 hours of Universal Time).
     * The procedure was tested to be good since 1 March, -100100 (of both
     * calendars) up to a few million years into the future.
     *
     * @param int $gy Calendar year (years BC numbered 0, -1, -2, ...)
     * @param int $gm Calendar month (1 to 12)
     * @param int $gd Calendar day of the month (1 to 28/29/30/31)
     * @return int Julian Day number
     */
    public static function g2d(int $gy, int $gm, int $gd): int
    {
        return (self::div(($gy + self::div($gm - 8, 6) + 100100) * 1461, 4)
                + self::div(153 * self::mod($gm + 9, 12) + 2, 5)
                + $gd - 34840408
            ) - self::div(self::div($gy + 100100 + self::div($gm - 8, 6), 100) * 3, 4) + 752;
    }

    /**
     * Converts a date of the jalali calendar to the Julian Day number.
     *
     * @param int $jy jalali year (1 to 3100)
     * @param int $jm jalali month (1 to 12)
     * @param int $jd jalali day (1 to 29/31)
     * @return int Julian Day number
     */
    public static function j2d(int $jy, int $jm, int $jd): int
    {
        $jCal = self::jalaliCal($jy);

        return self::g2d($jCal['gy'], 3, $jCal['march']) + ($jm - 1) * 31 - self::div($jm, 7) * ($jm - 7) + $jd - 1;
    }

    /**
     * Converts the Julian Day number to a date in the jalali calendar.
     *
     * @param int $jdn Julian Day number
     * @return array{0:int,1:int,2:int}
     *               0: jalali year (1 to 3100)
     *               1: jalali month (1 to 12)
     *               2: jalali day (1 to 29/31)
     */
    public static function d2j(int $jdn): array
    {
        $gy = self::d2g($jdn)[0];
        $jy = $gy - 621;
        $jCal = self::jalaliCal($jy);
        $jdn1f = self::g2d($gy, 3, $jCal['march']);

        $k = $jdn - $jdn1f;

        if ($k >= 0) {
            if ($k <= 185) {
                $jm = 1 + self::div($k, 31);
                $jd = self::mod($k, 31) + 1;

                return [$jy, $jm, $jd];
            } else {
                $k -= 186;
            }
        } else {
            $jy -= 1;
            $k += 179;

            if ($jCal['leap'] === 1) {
                $k += 1;
            }
        }

        $jm = 7 + self::div($k, 30);
        $jd = self::mod($k, 30) + 1;

        return [$jy, $jm, $jd];
    }

    /**
     * @throws Exception
     */
    public static function date(string $format, \DateTime|false $stamp = false, string|null|\DateTimeZone $timezone = null): string
    {
        $stamp = ($stamp !== false) ? $stamp : time();
        $dateTime = static::createDateTime($stamp, $timezone);

        //Find what to replace
        $chars = ((bool)preg_match_all('/([a-zA-Z]{1})/', $format, $chars)) ? $chars[0] : [];

        //Intact Keys
        $intact = ['B', 'h', 'H', 'g', 'G', 'i', 's', 'I', 'U', 'u', 'Z', 'O', 'P'];
        $intact = self::filterArray($chars, $intact);
        $intactValues = [];

        foreach ($intact as $k => $v) {
            $intactValues[$k] = $dateTime->format($v);
        }
        //End Intact Keys

        //Changed Keys
        [$year, $month, $day] = [(int)$dateTime->format('Y'), (int)$dateTime->format('n'), (int)$dateTime->format('j')];
        [$jYear, $jMonth, $jDay] = self::toJalali($year, $month, $day);

        $keys = [
            'd',
            'D',
            'j',
            'l',
            'N',
            'S',
            'w',
            'z',
            'W',
            'F',
            'm',
            'M',
            'n',
            't',
            'L',
            'o',
            'Y',
            'y',
            'a',
            'A',
            'c',
            'r',
            'e',
            'T',
        ];
        $keys = self::filterArray($chars, $keys, ['z']);
        $values = [];

        foreach ($keys as $k => $key) {
            $v = '';
            switch ($key) {
                //Day
                case 'd':
                    $v = sprintf('%02d', $jDay);
                    break;
                case 'D':
                    $v = self::getDayNames($dateTime->format('D'), true);
                    break;
                case 'j':
                    $v = $jDay;
                    break;
                case 'l':
                    $v = self::getDayNames($dateTime->format('l'));
                    break;
                case 'N':
                    $v = self::getDayNames($dateTime->format('l'), false, 1, true);
                    break;
                case 'S':
                    $v = 'ام';
                    break;
                case 'w':
                    $v = self::getDayNames($dateTime->format('l'), false, 1, true) - 1;
                    break;
                case 'z':
                    if ($jMonth > 6) {
                        $v = 186 + (($jMonth - 6 - 1) * 30) + $jDay;
                    } else {
                        $v = (($jMonth - 1) * 31) + $jDay;
                    }
                    self::$temp['z'] = (string)$v;
                    break;
                //Week
                case 'W':
                    $v = is_int((int)self::$temp['z'] / 7) ? ((int)self::$temp['z'] / 7) : intval((int)self::$temp['z'] / 7 + 1);
                    break;
                //Month
                case 'F':
                    $v = self::getMonthName($jMonth);
                    break;
                case 'm':
                    $v = sprintf('%02d', $jMonth);
                    break;
                case 'M':
                    $v = self::getMonthName($jMonth, true);
                    break;
                case 'n':
                    $v = $jMonth;
                    break;
                case 't':
                    $v = ($jMonth === 12) ? (self::isLeapJalaliYear($jYear) ? 30 : 29) : ($jMonth > 6 ? 30 : 31);
                    break;
                //Year
                case 'L':
                    $tmpObj = static::createDateTime(time() - 31536000, $timezone);
                    $v = $tmpObj->format('L');
                    break;
                case 'o':
                case 'Y':
                    $v = $jYear;
                    break;
                case 'y':
                    $v = $jYear % 100;
                    if ($v < 10) {
                        $v = '0' . $v;
                    }
                    break;
                //Time
                case 'a':
                    $v = ($dateTime->format('a') === 'am') ? 'ق.ظ' : 'ب.ظ';
                    break;
                case 'A':
                    $v = ($dateTime->format('A') === 'AM') ? 'قبل از ظهر' : 'بعد از ظهر';
                    break;
                //Full Dates
                case 'c':
                    $v = $jYear . '-' . sprintf('%02d', $jMonth) . '-' . sprintf('%02d', $jDay) . 'T';
                    $v .= $dateTime->format('H') . ':' . $dateTime->format('i') . ':' . $dateTime->format('s') . $dateTime->format('P');
                    break;
                case 'r':
                    $v = self::getDayNames($dateTime->format('D'), true) . ', ' . sprintf(
                            '%02d',
                            $jDay
                        ) . ' ' . self::getMonthName($jMonth, true);
                    $v .= ' ' . $jYear . ' ' . $dateTime->format('H') . ':' . $dateTime->format('i') . ':' . $dateTime->format('s') . ' ' . $dateTime->format('P');
                    break;
                //Timezone
                case 'e':
                    $v = $dateTime->format('e');
                    break;
                case 'T':
                    $v = $dateTime->format('T');
                    break;
            }
            $values[$k] = $v;
        }
        //End Changed Keys

        //Merge
        $keys = array_merge($intact, $keys);
        $values = array_merge($intactValues, $values);

        return strtr($format, array_combine($keys, $values));
    }

    public static function strftime(string $format, Carbon|false $stamp = false, string|null|\DateTimeZone $timezone = null): string
    {
        $str_format_code = [
            '%a',
            '%A',
            '%d',
            '%e',
            '%j',
            '%u',
            '%w',
            '%U',
            '%V',
            '%W',
            '%b',
            '%B',
            '%h',
            '%m',
            '%C',
            '%g',
            '%G',
            '%y',
            '%Y',
            '%H',
            '%I',
            '%l',
            '%M',
            '%p',
            '%P',
            '%r',
            '%R',
            '%S',
            '%T',
            '%X',
            '%z',
            '%Z',
            '%c',
            '%D',
            '%F',
            '%s',
            '%x',
            '%n',
            '%t',
            '%%',
        ];

        $date_format_code = [
            'D',
            'l',
            'd',
            'j',
            'z',
            'N',
            'w',
            'W',
            'W',
            'W',
            'M',
            'F',
            'M',
            'm',
            'y',
            'y',
            'y',
            'y',
            'Y',
            'H',
            'h',
            'g',
            'i',
            'A',
            'a',
            'h:i:s A',
            'H:i',
            's',
            'H:i:s',
            'h:i:s',
            'H',
            'H',
            'D j M H:i:s',
            'd/m/y',
            'Y-m-d',
            'U',
            'd/m/y',
            "\n",
            "\t",
            '%',
        ];

        //Change Strftime format to Date format
        $format = str_replace($str_format_code, $date_format_code, $format);

        //Convert to date
        return self::date($format, $stamp, $timezone);
    }

    /**
     * @return ($numeric is true ? int : string)
     */
    private static function getDayNames(string $day, bool $shorten = false, int $len = 1, bool $numeric = false): int|string
    {
        switch (strtolower($day)) {
            case 'sat':
            case 'saturday':
                $ret = 'شنبه';
                $n = 1;
                break;
            case 'sun':
            case 'sunday':
                $ret = 'یکشنبه';
                $n = 2;
                break;
            case 'mon':
            case 'monday':
                $ret = 'دوشنبه';
                $n = 3;
                break;
            case 'tue':
            case 'tuesday':
                $ret = 'سه‌شنبه';
                $n = 4;
                break;
            case 'wed':
            case 'wednesday':
                $ret = 'چهارشنبه';
                $n = 5;
                break;
            case 'thu':
            case 'thursday':
                $ret = 'پنج‌شنبه';
                $n = 6;
                break;
            case 'fri':
            case 'friday':
                $ret = 'جمعه';
                $n = 7;
                break;
            default:
                $ret = '';
                $n = -1;
        }

        return ($numeric) ? $n : (($shorten) ? mb_substr($ret, 0, $len, 'UTF-8') : $ret);
    }

    private static function getMonthName(int $month, bool $shorten = false, int $len = 3): string
    {
        $monthIndex = ($month) - 1;
        $monthName = self::$monthNames[$monthIndex];

        return ($shorten) ? mb_substr($monthName, 0, $len, 'UTF-8') : $monthName;
    }

    /** @phpstan-ignore-next-line */
    private static function filterArray(array $needle, array $haystack, array $always = []): array
    {
        foreach ($haystack as $k => $v) {
            if (!in_array($v, $needle, true) && !in_array($v, $always, true)) {
                unset($haystack[$k]);
            }
        }

        return $haystack;
    }

    /**
     * @return array{'year':int,'month':int,'day':int,'hour':int,'minute':int,'second':int}
     */
    public static function parseFromFormat(string $format, string $date): array
    {
        // reverse engineer date formats
        $keys = [
            'Y' => ['year', '\d{4}'],
            'y' => ['year', '\d{2}'],
            'm' => ['month', '\d{2}'],
            'n' => ['month', '\d{1,2}'],
            'M' => ['month', '[A-Z][a-z]{3}'],
            'F' => ['month', '[A-Z][a-z]{2,8}'],
            'd' => ['day', '\d{2}'],
            'j' => ['day', '\d{1,2}'],
            'D' => ['day', '[A-Z][a-z]{2}'],
            'l' => ['day', '[A-Z][a-z]{6,9}'],
            'u' => ['hour', '\d{1,6}'],
            'h' => ['hour', '\d{2}'],
            'H' => ['hour', '\d{2}'],
            'g' => ['hour', '\d{1,2}'],
            'G' => ['hour', '\d{1,2}'],
            'i' => ['minute', '\d{2}'],
            's' => ['second', '\d{2}'],
        ];

        // convert format string to regex
        $regex = '';
        $chars = str_split($format);
        foreach ($chars as $n => $char) {
            $lastChar = $chars[$n - 1] ?? '';
            $skipCurrent = $lastChar == '\\';
            if (!$skipCurrent && isset($keys[$char])) {
                $regex .= '(?P<' . $keys[$char][0] . '>' . $keys[$char][1] . ')';
            } else {
                if ($char == '\\') {
                    $regex .= $char;
                } else {
                    $regex .= preg_quote($char);
                }
            }
        }

        $dt = [];
        $dt['error_count'] = 0;
        // now try to match it
        if (preg_match('#^' . $regex . '$#', $date, $dt)) {
            foreach ($dt as $k => $v) {
                if (is_int($k)) {
                    unset($dt[$k]);
                }
            }
            if (!CalendarUtils::checkdate((int)$dt['month'], (int)$dt['day'], (int)$dt['year'], false)) {
                $dt['error_count'] = 1;
            }
        } else {
            $dt['error_count'] = 1;
        }
        $dt['errors'] = [];
        $dt['fraction'] = '';
        $dt['warning_count'] = 0;
        $dt['warnings'] = [];
        $dt['is_localtime'] = 0;
        $dt['zone_type'] = 0;
        $dt['zone'] = 0;
        $dt['is_dst'] = '';

        if (is_string($dt['year']) && strlen($dt['year']) === 2) {
            $now = Jalali::forge('now');
            $x = (int)$now->format('Y') - (int)$now->format('y');
            $dt['year'] = (int)$dt['year'] + $x;
        }

        $dt['year'] = isset($dt['year']) ? (int)$dt['year'] : 0;
        $dt['month'] = isset($dt['month']) ? (int)$dt['month'] : 0;
        $dt['day'] = isset($dt['day']) ? (int)$dt['day'] : 0;
        $dt['hour'] = isset($dt['hour']) ? (int)$dt['hour'] : 0;
        $dt['minute'] = isset($dt['minute']) ? (int)$dt['minute'] : 0;
        $dt['second'] = isset($dt['second']) ? (int)$dt['second'] : 0;

        return $dt;
    }

    /**
     * @throws Exception
     */
    public static function createDatetimeFromFormat(string $format, string $str, \DateTimeZone|null $timezone = null): \DateTime
    {
        $pd = self::parseFromFormat($format, $str);
        $gd = self::toGregorian((int)$pd['year'], (int)$pd['month'], (int)$pd['day']);
        $date = self::createDateTime('now', $timezone);
        $date->setDate($gd[0], $gd[1], $gd[2]);
        $date->setTime($pd['hour'], $pd['minute'], $pd['second']);

        return $date;
    }

    public static function createCarbonFromFormat(string $format, string $str, \DateTimeZone|null $timezone = null): Carbon
    {
        $dateTime = self::createDatetimeFromFormat($format, $str, $timezone);

        return Carbon::createFromTimestamp($dateTime->getTimestamp(), $dateTime->getTimezone());
    }

    /**
     * Convert Latin numbers to persian numbers and vice versa
     */
    public static function convertNumbers(string $string, bool $toLatin = false): string
    {
        $farsi_array = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english_array = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        if (!$toLatin) {
            return str_replace($english_array, $farsi_array, $string);
        }

        return str_replace($farsi_array, $english_array, $string);
    }

    /**
     * @throws Exception
     */
    public static function createDateTime(null|int|string|\DateTimeInterface $timestamp = null, null|string|\DateTimeZone $timezone = null): \DateTime
    {
        $timezone = static::createTimeZone($timezone);

        if ($timestamp === null) {
            return Carbon::now($timezone);
        }

        if ($timestamp instanceof \DateTime) {
            return $timestamp;
        }

        if (is_string($timestamp)) {
            return new \DateTime($timestamp, $timezone);
        }

        if (is_numeric($timestamp)) {
            return Carbon::createFromTimestamp($timestamp, $timezone);
        }

        throw new \InvalidArgumentException('timestamp is not valid');
    }

    /**
     * @throws Exception
     */
    public static function createTimeZone(\DateTimeZone|null|string $timezone = null): \DateTimeZone
    {
        if ($timezone instanceof \DateTimeZone) {
            return $timezone;
        }

        if ($timezone === null) {
            return new \DateTimeZone(date_default_timezone_get());
        }

        return new \DateTimeZone($timezone);
    }
}
