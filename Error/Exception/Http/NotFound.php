<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class NotFound extends Http
{
    
    public $doLog = false;
    
    protected $_httpStatus = 404;
    
    public function getErrors()
    {
        return '';
    }
}
