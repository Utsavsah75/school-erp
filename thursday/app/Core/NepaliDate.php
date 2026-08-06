<?php

namespace App\Core;

/**
 * Bikram Sambat (BS) <-> Gregorian (AD) date conversion.
 *
 * Nepal's official calendar is lunar-solar: each BS month runs 29-32
 * days and the pattern is not formula-derivable, so conversion works
 * off a verified day-count table (BS 2000-2099, matching the anchor
 * BS 2000/01/01 = AD 1943/04/14 used by Nepal's civil calendar) rather
 * than an approximation. Covers roughly AD 1943-2043.
 *
 * Used throughout the Fee module wherever a "Fee Date" / "Due Date" /
 * "Payment Date" needs a BS picker; the AD equivalent is what actually
 * gets stored in indexed DATE columns, with the BS string kept
 * alongside for display (see fee_module_migration.sql).
 */
class NepaliDate
{
    /** Anchor: first day of BS 2000 in the Gregorian calendar. */
    private const EPOCH_BS_YEAR = 2000;
    private const EPOCH_AD = '1943-04-14';

    /** @var array<int,int[]> BS year => [days in Baisakh..Chaitra] */
    private const MONTH_DAYS = [
        2000 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2001 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2002 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2003 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2004 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2005 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2006 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2007 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2008 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2009 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2010 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2011 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2012 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2013 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2014 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2015 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2016 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2017 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2018 => [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2019 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2020 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2021 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2022 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2023 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2024 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2025 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2026 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2027 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2028 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2029 => [31, 31, 32, 31, 32, 30, 30, 29, 30, 29, 30, 30],
        2030 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2031 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2032 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2033 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2034 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2035 => [30, 32, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2036 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2037 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2038 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2039 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2040 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2041 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2042 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2043 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2044 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2045 => [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2046 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2047 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2048 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2049 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2050 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2051 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2052 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2053 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2054 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2055 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2056 => [31, 31, 32, 31, 32, 30, 30, 29, 30, 29, 30, 30],
        2057 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2058 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2059 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2060 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2061 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2062 => [30, 32, 31, 32, 31, 31, 29, 30, 29, 30, 29, 31],
        2063 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2064 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2065 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2066 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2067 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2068 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2069 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2070 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2071 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2072 => [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2073 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2074 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2075 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2076 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2077 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2078 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2079 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2080 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2081 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2082 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2083 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2084 => [31, 31, 32, 31, 31, 30, 30, 30, 29, 30, 30, 30],
        2085 => [31, 32, 31, 32, 30, 31, 30, 30, 29, 30, 30, 30],
        2086 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2087 => [31, 31, 32, 31, 31, 31, 30, 30, 29, 30, 30, 30],
        2088 => [30, 31, 32, 32, 30, 31, 30, 30, 29, 30, 30, 30],
        2089 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2090 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2091 => [31, 31, 32, 31, 31, 31, 30, 30, 29, 30, 30, 30],
        2092 => [30, 31, 32, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2093 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2094 => [31, 31, 32, 31, 31, 30, 30, 30, 29, 30, 30, 30],
        2095 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 30, 30, 30],
        2096 => [30, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2097 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2098 => [31, 31, 32, 31, 31, 31, 29, 30, 29, 30, 29, 31],
        2099 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
    ];

    public const MONTH_NAMES = [
        1 => 'Baishakh', 2 => 'Jestha', 3 => 'Ashadh', 4 => 'Shrawan',
        5 => 'Bhadra', 6 => 'Ashwin', 7 => 'Kartik', 8 => 'Mangsir',
        9 => 'Poush', 10 => 'Magh', 11 => 'Falgun', 12 => 'Chaitra',
    ];

    public static function minBsYear(): int
    {
        return self::EPOCH_BS_YEAR;
    }

    public static function maxBsYear(): int
    {
        return (int) max(array_keys(self::MONTH_DAYS));
    }

    public static function daysInMonth(int $bsYear, int $bsMonth): int
    {
        if (!isset(self::MONTH_DAYS[$bsYear]) || $bsMonth < 1 || $bsMonth > 12) {
            throw new \InvalidArgumentException("BS date out of supported range (2000-2099): {$bsYear}-{$bsMonth}");
        }
        return self::MONTH_DAYS[$bsYear][$bsMonth - 1];
    }

    public static function toAd(int $bsYear, int $bsMonth, int $bsDay): string
    {
        $daysFromEpoch = self::daysFromEpoch($bsYear, $bsMonth, $bsDay);
        $epoch = new \DateTimeImmutable(self::EPOCH_AD);
        return $epoch->modify("+{$daysFromEpoch} days")->format('Y-m-d');
    }

    /** @return array{0:int,1:int,2:int} */
    public static function fromAd(string|\DateTimeInterface $adDate): array
    {
        $ad = $adDate instanceof \DateTimeInterface ? $adDate : new \DateTimeImmutable($adDate);
        $epoch = new \DateTimeImmutable(self::EPOCH_AD);
        $diffDays = (int) $epoch->diff($ad)->format('%r%a');

        if ($diffDays < 0) {
            throw new \InvalidArgumentException('Date is before the supported BS range (AD 1943-04-14).');
        }

        $bsYear = self::EPOCH_BS_YEAR;
        $bsMonth = 1;
        $remaining = $diffDays;

        while (true) {
            if (!isset(self::MONTH_DAYS[$bsYear])) {
                throw new \InvalidArgumentException('Date is beyond the supported BS range (BS 2099).');
            }
            $daysInThisMonth = self::MONTH_DAYS[$bsYear][$bsMonth - 1];
            if ($remaining < $daysInThisMonth) {
                break;
            }
            $remaining -= $daysInThisMonth;
            $bsMonth++;
            if ($bsMonth > 12) {
                $bsMonth = 1;
                $bsYear++;
            }
        }

        return [$bsYear, $bsMonth, $remaining + 1];
    }

    public static function adToBsString(string|\DateTimeInterface $adDate): string
    {
        [$y, $m, $d] = self::fromAd($adDate);
        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    public static function bsStringToAd(string $bsDate): string
    {
        [$y, $m, $d] = self::parseBsString($bsDate);
        return self::toAd($y, $m, $d);
    }

    /** @return array{0:int,1:int,2:int} */
    public static function parseBsString(string $bsDate): array
    {
        $parts = preg_split('/[-\/]/', trim($bsDate));
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException("Invalid BS date format: {$bsDate}");
        }
        return [(int) $parts[0], (int) $parts[1], (int) $parts[2]];
    }

    public static function todayBsString(): string
    {
        return self::adToBsString(new \DateTimeImmutable('now'));
    }

    private static function daysFromEpoch(int $bsYear, int $bsMonth, int $bsDay): int
    {
        if (!isset(self::MONTH_DAYS[$bsYear]) || $bsMonth < 1 || $bsMonth > 12) {
            throw new \InvalidArgumentException("BS date out of supported range (2000-2099): {$bsYear}-{$bsMonth}-{$bsDay}");
        }

        $days = 0;
        for ($y = self::EPOCH_BS_YEAR; $y < $bsYear; $y++) {
            if (!isset(self::MONTH_DAYS[$y])) {
                throw new \InvalidArgumentException('Date is beyond the supported BS range (BS 2099).');
            }
            $days += array_sum(self::MONTH_DAYS[$y]);
        }
        for ($m = 1; $m < $bsMonth; $m++) {
            $days += self::MONTH_DAYS[$bsYear][$m - 1];
        }
        $days += $bsDay - 1;

        return $days;
    }
}
