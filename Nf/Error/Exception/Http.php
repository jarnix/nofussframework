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
        if ($response->getContentType()=='json') {
            $response->addBodyPart(json_encode(['message' => $this->getMessage()]));
        } else {
            $response->addBodyPart($this->getMessage());
        }
        $response->sendResponse();
    }
}
