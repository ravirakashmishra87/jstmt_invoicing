<?php

namespace App\Services;

class AmountInWords
{
    private static array $ones = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private static array $tens = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public static function convert(float|string|null $amount, string $prefix = 'Indian Rupees ', string $suffix = ' Only'): string
    {
        $amount = round((float) ($amount ?? 0), 2);
        $negative = $amount < 0;
        $amount = abs($amount);

        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = self::convertInteger($rupees);
        if ($words === '') {
            $words = 'Zero';
        }

        $result = $prefix.trim($words);

        if ($paise > 0) {
            $result .= ' and '.self::convertInteger($paise).' Paise';
        }

        $result .= $suffix;

        return ($negative ? 'Minus ' : '').$result;
    }

    public static function taxAmount(float|string|null $amount): string
    {
        return self::convert($amount, 'INR ', ' Only');
    }

    private static function convertInteger(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $parts = [];

        $crore = intdiv($number, 10000000);
        $number %= 10000000;
        if ($crore > 0) {
            $parts[] = self::convertBelowThousand($crore).' Crore';
        }

        $lakh = intdiv($number, 100000);
        $number %= 100000;
        if ($lakh > 0) {
            $parts[] = self::convertBelowThousand($lakh).' Lakh';
        }

        $thousand = intdiv($number, 1000);
        $number %= 1000;
        if ($thousand > 0) {
            $parts[] = self::convertBelowThousand($thousand).' Thousand';
        }

        if ($number > 0) {
            $parts[] = self::convertBelowThousand($number);
        }

        return implode(' ', $parts);
    }

    private static function convertBelowThousand(int $number): string
    {
        $parts = [];

        $hundred = intdiv($number, 100);
        $number %= 100;
        if ($hundred > 0) {
            $parts[] = self::$ones[$hundred].' Hundred';
        }

        if ($number > 0) {
            if ($number < 20) {
                $parts[] = self::$ones[$number];
            } else {
                $parts[] = trim(self::$tens[intdiv($number, 10)].' '.self::$ones[$number % 10]);
            }
        }

        return implode(' ', $parts);
    }
}
