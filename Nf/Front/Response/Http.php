<?php
namespace Nf\Front\Response;

class Http extends AbstractResponse
{

    const SEPARATOR = 'separator';

    const SEPARATOR_ALT = "\n";

    const NEWLINE = '<br>';

    const NEWLINE_ALT = "\n";

    const TAB = " ";

    const TAB_ALT = " ";

    private $contentType = 'html';

    private $isBinaryContent = false;
    
    private $encoding = 'utf-8';

    protected $_headers = array();

    protected $_headersRaw = array();

    protected $_httpResponseCode = 200;

    protected $_isRedirect = false;

    protected function _normalizeHeader($name)
    {
        $filtered = str_replace(array(
            '-',
            '_'
        ), ' ', (string) $name);
        $filtered = ucwords(strtolower($filtered));
        $filtered = str_replace(' ', '-', $filtered);
        return $filtered;
    }

    public function setHeader($name, $value, $replace = false)
    {
        $this->canSendHeaders(true);
        $name = $this->_normalizeHeader($name);
        $value = (string) $value;
        
        if ($replace) {
            foreach ($this->_headers as $key => $header) {
                if ($name == $header['name']) {
                    unset($this->_headers[$key]);
                }
            }
        }
        $this->_headers[] = array(
            'name' => $name,
            'value' => $value,
            'replace' => $replace
        );
        return $this;
    }

    public function redirect($url, $code = 302, $exit = true)
    {
        $this->canSendHeaders();
        $this->setHeader('Location', $url, true)->setHttpResponseCode($code);
        if ($exit) {
            $front = \Nf\Front::getInstance();
            $front->postLaunchAction();
            $this->clearBuffer();
            $this->clearBody();
            $this->sendHeaders();
            exit();
        }
        return $this;
    }

    public function isRedirect()
    {
        return $this->_isRedirect;
    }

    public function getHeaders()
    {
        return $this->_headers;
    }

    public function clearHeaders()
    {
        $this->_headers = array();
        
        return $this;
    }

    public function clearHeader($name)
    {
        if (! count($this->_headers)) {
            return $this;
        }
        foreach ($this->_headers as $index => $header) {
            if ($name == $header['name']) {
                unset($this->_headers[$index]);
            }
        }
        return $this;
    }

    public function setRawHeader($value)
    {
        $this->canSendHeaders();
        if ('Location' == substr($value, 0, 8)) {
            $this->_isRedirect = true;
        }
        $this->_headersRaw[] = (string) $value;
        return $this;
    }

    public function clearRawHeaders()
    {
        $this->_headersRaw = array();
        return $this;
    }

    public function clearRawHeader($headerRaw)
    {
        if (! count($this->_headersRaw)) {
            return $this;
        }
        $key = array_search($headerRaw, $this->_headersRaw);
        unset($this->_headersRaw[$key]);
        return $this;
    }

    public function clearAllHeaders()
    {
        return $this->clearHeaders()->clearRawHeaders();
    }

    public function setHttpResponseCode($code)
    {
        if (! is_int($code) || (100 > $code) || (599 < $code)) {
            throw new \Exception('Invalid HTTP response code');
        }
        if ((300 <= $code) && (307 >= $code)) {
            $this->_isRedirect = true;
        } else {
            $this->_isRedirect = false;
        }
        $this->_httpResponseCode = $code;
        return $this;
    }

    public function canSendHeaders()
    {
        $headersSent = headers_sent($file, $line);
        if ($headersSent) {
            trigger_error('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
        }
        return ! $headersSent;
    }

    public function sendHeaders()
    {
        // Only check if we can send headers if we have headers to send
        if (count($this->_headersRaw) || count($this->_headers) || (200 != $this->_httpResponseCode)) {
            $this->canSendHeaders();
        } elseif (200 == $this->_httpResponseCode) {
            // Haven't changed the response code, and we have no headers
            return $this;
        }
        
        $httpCodeSent = false;
        
        foreach ($this->_headersRaw as $header) {
            if (! $httpCodeSent && $this->_httpResponseCode) {
                header($header, true, $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header);
            }
        }
        
        foreach ($this->_headers as $header) {
            if (! $httpCodeSent && $this->_httpResponseCode) {
                header($header['name'] . ': ' . $header['value'], $header['replace'], $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
        }
        
        if (! $httpCodeSent) {
            header('HTTP/1.1 ' . $this->_httpResponseCode);
            $httpCodeSent = true;
        }
        
        return $this;
    }

    public function displayError($err, $isXhr = false)
    {
        // removes the cache headers if there is an error
        $this->setCacheable(0);
        if ($isXhr || $this->contentType!='html') {
            $this->setContentType('text');
            echo 'Error' . self::NEWLINE_ALT;
            echo strip_tags(self::displayErrorHelper($err, true));
            echo 'Error' . self::NEWLINE_ALT;
        } else {
            echo '<pre style="color:#555; line-height:16px;"><span style="color:red;">Error</span><br />';
            echo self::displayErrorHelper($err, false);
            echo '</pre>';
        }
    }

    protected static function boldText($text, $alternativeSeparator = false)
    {
        if ($alternativeSeparator) {
            return '* ' . $text . ' *';
        } else {
            return '<b>' . $text . '</b>';
        }
    }

    protected static function preFormatErrorText($beginOrEnd, $alternativeSeparator)
    {
        if ($alternativeSeparator) {
            return ($beginOrEnd == 0) ? '' : '';
        } else {
            return ($beginOrEnd == 0) ? '<pre>' : '</pre>';
        }
    }
    
    // sends header to allow the browser to cache the response a given time
    public function setCacheable($minutes)
    {
        if ($minutes <= 0) {
            $this->setHeader('Cache-Control', 'private, no-cache, no-store, must-revalidate');
            $this->setHeader('Expires', '-1');
            $this->setHeader('Pragma', 'no-cache');
        } else {
            $this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $minutes * 60) . ' GMT', true);
            $this->setHeader('Cache-Control', 'max-age=' . $minutes * 60, true);
            $this->setHeader('Pragma', 'public', true);
        }
    }

    public function getContentType()
    {
        return $this->contentType;
    }
    
    public function isBinary()
    {
        return $this->isBinaryContent;
    }

    public function setContentType($type = 'html')
    {
        $this->contentType = $type;
        $this->isBinaryContent = false;
        $type = strtolower($type);
        switch ($type) {
            case 'atom':
                $this->setHeader('content-type', 'application/atom+xml');
                break;
            case 'css':
                $this->setHeader('content-type', 'text/css');
                break;
            case 'gif':
                $this->setHeader('content-type', 'image/gif');
                $this->isBinaryContent = true;
                break;
            case 'jpeg':
            case 'jpg':
                $this->setHeader('content-type', 'image/jpeg');
                $this->isBinaryContent = true;
                break;
            case 'png':
                $this->setHeader('content-type', 'image/png');
                $this->isBinaryContent = true;
                break;
            case 'js':
            case 'javascript':
                $this->setHeader('content-type', 'text/javascript');
                break;
            case 'json':
                $this->setHeader('content-type', 'application/json');
                break;
            case 'pdf':
                $this->setHeader('content-type', 'application/pdf');
                $this->isBinaryContent = true;
                break;
            case 'rss':
                $this->setHeader('content-type', 'application/rss+xml');
                break;
            case 'text':
                $this->setHeader('content-type', 'text/plain');
                break;
            case 'xml':
                $this->setHeader('content-type', 'text/xml');
                break;
            case 'html':
                $this->setHeader('content-type', 'text/html');
                break;
            default:
                throw new \Exception('This content type was not found: "' . $type . '"');
        }
    }
    
    public function getEncoding()
    {
        return $this->encoding;
    }
    
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    public static function colorText($text, $color, $alternativeSeparator = false)
    {
        if (!$alternativeSeparator) {
            return '<span style="color:' . $color . '">' . $text . '</span>';
        } else {
            return $text;
        }
    }

    protected static function escape($str)
    {
        return strip_tags($str);
    }
}
