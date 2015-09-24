<?php

namespace Nf;

class UserAgent
{

    public $httpUserAgent;
    public $lowerHttpUserAgent;

    public function __construct($httpUserAgent)
    {
        $this->httpUserAgent=$httpUserAgent;
        $this->lowerHttpUserAgent=strtolower($httpUserAgent);
    }

    public function checkClass($class)
    {
        switch ($class) {
            case 'iphone':
                return $this->isIphone();
                break;
            case 'ipad':
                return $this->isIpad();
                break;
            case 'androidmobile':
                return $this->isAndroidMobile();
                break;
            case 'androidtablet':
                return $this->isAndroidTablet();
                break;
            case 'blackberry':
                return $this->isBlackberry();
                break;
        }
    }

    public function isIphone()
    {
        return strstr($this->lowerHttpUserAgent, 'iphone') || strstr($this->lowerHttpUserAgent, 'ipod');
    }

    public function isIpad()
    {
        return strstr($this->lowerHttpUserAgent, 'ipad');
    }

    public function isAndroidMobile()
    {
        return (strstr($this->lowerHttpUserAgent, 'android')!==false) && (strstr($this->lowerHttpUserAgent, 'mobile')===false);
    }

    public function isAndroidTablet()
    {
        return (strstr($this->lowerHttpUserAgent, 'android')!==false) && (strstr($this->lowerHttpUserAgent, 'mobile')===false);
    }

    public function isBlackberry()
    {
        return strstr($this->lowerHttpUserAgent, 'blackberry');
    }
}
