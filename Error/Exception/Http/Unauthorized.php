<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class Unauthorized extends Http
{
    
    public $doLog = false;
    
    protected $_httpStatus = 401;
    
    public function getErrors()
    {
        return '';
    }
}
