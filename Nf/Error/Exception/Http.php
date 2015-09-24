<?php
namespace Nf\Error\Exception;

class Http extends \Exception
{

    protected $_httpStatus = 500;
    
    public $doLog = true;

    public function getHttpStatus()
    {
        return $this->_httpStatus;
    }
    
    public function display()
    {
        $front = \Nf\Front::getInstance();
        $response = $front->getResponse();
        $response->sendResponse();
    }
}
