<?php
namespace Nf\Front\Response;

abstract class AbstractResponse
{

    protected $_bodyParts = array();

    public function addBodyPart($bodyPart)
    {
        $this->_bodyParts[] = $bodyPart;
    }

    public function clearBody()
    {
        $this->_bodyParts = array();
    }

    public function clearBuffer()
    {
        $maxObLevel = \Nf\Front::$obLevel;
        $curObLevel = ob_get_level();
        if ($curObLevel > $maxObLevel) {
            do {
                ob_end_clean();
                $curObLevel = ob_get_level();
            } while ($curObLevel > $maxObLevel);
        }
    }

    public function output()
    {
        echo implode('', $this->_bodyParts);
    }

    public function sendResponse()
    {
        $this->sendHeaders();
        $this->output();
    }

    public function setHttpResponseCode($code)
    {
    }

    public function isBinary()
    {
        return false;
    }

    public function getContentType()
    {
        return false;
    }

    public function setContentType($type = 'html')
    {
    }

    public static function displayErrorHelper($err, $alternativeSeparator = false)
    {
        $output = '';
        
        $separator = $alternativeSeparator ? static::NEWLINE_ALT : static::NEWLINE;
        
        if ($err['type'] != 'fatal') {
            $output .= static::colorText($err['type'] . ': ' . \Nf\Error\Handler::recursiveArrayToString(static::escape($err['message'])), 'red');
            $output .= $separator;
            $output .= static::colorText($err['file'] . ' (line ' . $err['line'] . ')', 'green', $alternativeSeparator);
            $output .= $separator . '-----' . $separator;
            $output .= implode($separator, self::getFileSample($err['file'], $err['line']));
            $output .= $separator . '-----' . $separator;
            $trace = $err['fullException']->getTrace();
            foreach ($trace as $entry) {
                $output .= self::stackTracePrintEntry($entry);
                if (isset($entry['file']) && isset($entry['line'])) {
                    $output .= '-----' . $separator;
                    $output .= implode($separator, self::getFileSample($entry['file'], $entry['line'], 2));
                    $output .= $separator . '-----' . $separator;
                }
            }
        } else {
            $output .= $err['message'] . $separator;
            $output .= static::preFormatErrorText(0, $alternativeSeparator);
            $output .= self::stackTracePrintEntry($err, 2, $alternativeSeparator);
            $output .= static::preFormatErrorText(1, $alternativeSeparator);
        }
        return $output;
    }

    public static function writeln($msg, $color = 'white')
    {
        echo static::colorText($msg, $color) . PHP_EOL;

    }

    protected static function stackTracePrintEntry($entry, $displayArgsType = 1, $alternativeSeparator = false)
    {
        $output = '';
        
        if (isset($entry['file'])) {
            $output .= static::colorText($entry['file'] . ' (line ' . $entry['line'] . ')', 'green', $alternativeSeparator);
            $output .= ($alternativeSeparator ? static::NEWLINE_ALT : static::NEWLINE);
        }
        if (isset($entry['class'])) {
            if ($entry['class'] != 'Nf\Error\Handler') {
                $output .= 'call: ' . $entry['class'] . '::';
                if (isset($entry['function'])) {
                    $output .= $entry['function'];
                    $output .= ($alternativeSeparator ? static::NEWLINE_ALT : static::NEWLINE);
                }
            }
        }
        
        if ($displayArgsType > 0 && isset($entry['args']) && count($entry['args'])) {
            $output .= static::stackTracePrintArgs($entry['args'], $alternativeSeparator);
            $output .= ($alternativeSeparator ? static::NEWLINE_ALT : static::NEWLINE);
        }
        return $output;
    }

    protected static function stackTracePrintArgs($args, $alternativeSeparator)
    {
        $output = '';
        $output .= 'arguments: ';
        $out = array();
        
        if (is_array($args)) {
            foreach ($args as $k => $v) {
                $forOut = '';
                $forOut = $k . ' = ';
                if (is_array($v) || is_object($v)) {
                    $strV = print_r($v, true);
                    if (strlen($strV) > 50) {
                        $strV = substr($strV, 0, 50) . '...';
                    }
                    $forReplace = [
                        "\n",
                        "\r"
                    ];
                    $forOut .= str_replace($forReplace, '', $strV);
                } else {
                    $forOut .= $v;
                }
                $out[] = $forOut;
            }
        }
        
        $output .= static::escape($alternativeSeparator ? static::TAB_ALT : static::TAB . '[ ' . implode(', ', $out) . ' ]');
        return $output;
    }

    protected static function getFileSample($filename, $line, $linesAround = 3)
    {
        $file = new \SplFileObject($filename);
        $currentLine = $line - $linesAround - 1;
        $sample = [];
        while ($currentLine >= 0 && ! $file->eof() && $currentLine < $line + $linesAround) {
            $file->seek($currentLine);
            $currentText = trim($file->current(), "\n\r");
            if ($currentLine == $line - 1) {
                $sample[] = $currentText;
            } else {
                $sample[] = static::colorText($currentText, 'bold_gray');
            }
            
            $currentLine ++;
        }
        return $sample;
    }
}
