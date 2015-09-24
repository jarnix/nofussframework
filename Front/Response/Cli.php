<?php
namespace Nf\Front\Response;

class Cli extends AbstractResponse
{

    const SEPARATOR = "\r\n";

    const NEWLINE = "\r\n";

    const TAB = "\t";

    public function setHeader($name, $value, $replace = false)
    {
        return true;
    }

    public function redirect($url, $code = 302)
    {
        throw new Exception('cannot redirect in cli version');
    }

    public function clearHeaders()
    {
        return false;
    }

    public function canSendHeaders()
    {
        return true;
    }

    public function sendHeaders()
    {
        return false;
    }

    public function displayError($err, $isXhr = false)
    {
        echo static::colorText('Error', 'red') . static::NEWLINE;
        echo self::displayErrorHelper($err);
    }

    protected static function boldText($text)
    {
        return self::colorText($text, 'green');
    }

    protected static function preFormatErrorText($beginOrEnd)
    {
        return '';
    }

    public static function colorText($text, $color = 'black')
    {
        $colors = array(
            'black' => '0;30',
            'dark_gray' => '1;30',
            'red' => '0;31',
            'bold_red' => '1;31',
            'green' => '0;32',
            'bold_green' => '1;32',
            'brown' => '0;33',
            'yellow' => '1;33',
            'blue' => '0;34',
            'bold_blue' => '1;34',
            'purple' => '0;35',
            'bold_purple' => '1;35',
            'cyan' => '0;36',
            'bold_cyan' => '1;36',
            'white' => '1;37',
            'bold_gray' => '0;37'
        );
        if (isset($colors[$color])) {
            return "\033[" . $colors[$color] . 'm' . $text . "\033[0m";
        }
    }

    protected static function escape($str)
    {
        return $str;
    }
}
