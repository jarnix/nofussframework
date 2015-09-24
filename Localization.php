<?php

namespace Nf;

use \IntlDateFormatter;
use \NumberFormatter;

class Localization extends Singleton
{

    protected static $_instance;

    protected $_currentLocale='fr_FR';

    const NONE=IntlDateFormatter::NONE;
    const SHORT=IntlDateFormatter::SHORT;
    const MEDIUM=IntlDateFormatter::MEDIUM;
    const LONG=IntlDateFormatter::LONG;
    const FULL=IntlDateFormatter::FULL;

    public static function normalizeLocale($str)
    {
        $str=str_replace('-', '_', $str);
        $arr=explode('_', $str);
        $out=strtolower($arr[0]) . '_' . strtoupper($arr[1]);
        return $out;
    }

    public static function setLocale($locale)
    {
        $instance=self::$_instance;
        $instance->_currentLocale=$locale;
    }

    public static function getLocale()
    {
        $instance=self::$_instance;
        return $instance->_currentLocale;
    }

    public static function formatDate($timestamp, $formatDate = self::SHORT, $formatTime = self::SHORT)
    {
        $instance=self::$_instance;
        $fmt=new IntlDateFormatter($instance->_currentLocale, $formatDate, $formatTime);
        return $fmt->format($timestamp);
    }

    // syntax can be found on : http://userguide.icu-project.org/formatparse/datetime
    public static function formatOther($timestamp, $format = 'eeee')
    {
        $instance=self::$_instance;
        $fmt=new IntlDateFormatter($instance->_currentLocale, 0, 0);
        $fmt->setPattern($format);
        return $fmt->format($timestamp);
    }

    public static function formatDay($timestamp, $fullName = true)
    {
        return self::formatOther($timestamp, ($fullName?'EEEE':'EEE'));
    }

    public static function formatMonth($timestamp, $fullName = true)
    {
        return self::formatOther($timestamp, ($fullName?'LLLL':'LLL'));
    }

    public static function formatCurrency($amount, $currency)
    {
        $instance=self::$_instance;
        $fmt = new NumberFormatter($instance->_currentLocale, NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($amount, $currency);
    }

    public static function formatNumber($value)
    {
        $instance=self::$_instance;
        $fmt = new NumberFormatter($instance->_currentLocale, NumberFormatter::DECIMAL);
        return $fmt->format($value);
    }

    public static function dateToTimestamp($date, $formatDate = self::SHORT, $formatTime = self::SHORT, $acceptISOFormat = false)
    {
        if (self::isTimestamp($date)) {
            return $date;
        } elseif ($acceptISOFormat && self::isISOFormat($date)) {
            $dt=new \DateTime($date);
            return $dt->getTimestamp();
        } else {
            $instance=self::$_instance;
            $fmt=new IntlDateFormatter($instance->_currentLocale, $formatDate, $formatTime);
            $timestamp=$fmt->parse($date);
            if ($timestamp) {
                return $timestamp;
            } else {
                throw new \Exception('input date is in another format and is not recognized:' . $date);
            }
        }
    }

    public static function isISOFormat($date)
    {
        if (preg_match('/\A(?:^([1-3][0-9]{3,3})-(0?[1-9]|1[0-2])-(0?[1-9]|[1-2][1-9]|3[0-1])\s([0-1][0-9]|2[0-4]):([0-5][0-9]):([0-5][0-9])$)\Z/im', $date)) {
            return true;
        } elseif (preg_match('/\A(?:^([1-3][0-9]{3,3})-(0?[1-9]|1[0-2])-(0?[1-9]|[1-2][1-9]|3[0-1])$)\Z/im', $date)) {
            return true;
        } else {
            return false;
        }
    }

    public static function isTimestamp($timestamp)
    {
        return ((string) (int) $timestamp === (string) $timestamp)
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }
}
