<?php

namespace App\Helpers;

class Utilities
{
    public static function getInfTypeString($value)
    {
        if ($value < 0) return 'deflasi';
        return 'inflasi';
    }
    public static function getInfTrendString($value)
    {
        if ($value < 0) return 'penurunan';
        return 'kenaikan';
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
