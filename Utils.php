<?php

namespace App\Service;

trait Utils
{
    //删除0X
    public static function remove0x($str)
    {
        if (substr($str, 0, 2) == '0x' || substr($str, 0, 2) == '0X') {
            $str = substr($str, 2);
        }
        return $str;
    }

    //十六进制转十进制
    public static function hexToDec($str)
    {
        $num = gmp_init($str);
        return gmp_strval($num, 10);
    }

    //十进制转16进制
    public static function decToHex($integer)
    {
        return dechex($integer);
    }

    //十进制转16进制加0x
    public static function decToHexAdd0x($integer)
    {
        return '0x' . self::decToHex($integer);
    }

    //处理精度
    public static function precision($string, $decimals)
    {
        if (is_string($string)) {
            $string = self::hexToDec($string);
        }
        if ($decimals != 1) {
            $pow = bcpow(10, $decimals);
        }
        return bcdiv($string, $pow, $decimals);
    }
}
