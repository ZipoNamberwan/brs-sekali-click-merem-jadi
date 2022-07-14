<?php

namespace App\Helpers;

class Utilities
{
    public static function getInfTypeString($value, bool $negate = false)
    {
        if ($value < 0) return !$negate ? 'deflasi' : 'inflasi';
        return !$negate ? 'inflasi' : 'deflasi';
    }
    public static function getInfTrendString($value, bool $negate = false)
    {
        if ($value < 0) return !$negate ? 'penurunan' : 'kenaikan';
        return !$negate ? 'kenaikan' : 'penurunan';
    }
    public static function isInflation($value)
    {
        if ($value < 0) return false;
        return true;
    }
    public static function getSentenceFromArray($array, $separator = ', ', $lastseparator = ' dan ')
    {
        $last  = array_slice($array, -1);
        $first = join($separator, array_slice($array, 0, -1));
        $both  = array_filter(array_merge(array($first), $last), 'strlen');
        return join($lastseparator, $both);
    }
    public static function getAreaType($code)
    {
        if ($code == '3100') return '';

        if (substr($code, 2, 1) == '7') {
            return 'Kota';
        } else {
            return 'Kabupaten';
        }
    }
    public static function getInfTrendSentence($inf)
    {
        if ($inf > 0)
            return 'mengalami kenaikan';
        else if ($inf < 0)
            return 'mengalami penurunan';
        else
            return 'tidak mengalami perubahan';
    }
    public static function isEnergyFoodInfStill($value)
    {
        if ($value == 0) return true;
        return false;
    }
    public static function getFormattedNumber($value, $dec = 2, $isAbsolute = true)
    {
        return number_format((float)($isAbsolute ? abs($value) : $value), $dec, ',', ' ');
    }
}
