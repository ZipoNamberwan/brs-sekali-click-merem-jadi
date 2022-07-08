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
    public static function getAbsoluteValue($value)
    {
        return abs($value);
    }
    public static function getSentenceFromArray($array, $separator = ', ', $lastseparator = ' dan ')
    {
        $last  = array_slice($array, -1);
        $first = join($separator, array_slice($array, 0, -1));
        $both  = array_filter(array_merge(array($first), $last), 'strlen');
        return join($lastseparator, $both);
    }
}
