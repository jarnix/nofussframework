<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class Gone extends Http
{
    
    public $doLog = false;
    
    protected $_httpStatus = 410;
    
    public function getErrors()
    {
        return '';
    }
}
