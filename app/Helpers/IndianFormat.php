<?php

namespace App\Helpers;

class IndianFormat
{
    public static function number(float|string|null $amount, int $decimals = 2): string
    {
        $amount = (float) ($amount ?? 0);
        $negative = $amount < 0;
        $amount = abs($amount);
        $formatted = number_format($amount, $decimals, '.', '');
        [$int, $dec] = array_pad(explode('.', $formatted), 2, str_repeat('0', $decimals));

        $lastThree = substr($int, -3);
        $rest = substr($int, 0, -3);
        if ($rest !== '' && $rest !== false) {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $int = $rest.','.$lastThree;
        } else {
            $int = $lastThree;
        }

        $result = $decimals > 0 ? $int.'.'.$dec : $int;

        return ($negative ? '-' : '').$result;
    }

    public static function money(float|string|null $amount): string
    {
        return '₹'.self::number($amount);
    }

    public static function date($date): string
    {
        if (! $date) {
            return '';
        }

        return \Carbon\Carbon::parse($date)->format('d-M-Y');
    }
}
