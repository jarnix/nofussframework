<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class InternalServerError extends Http
{
    
    public $doLog = false;
    
    protected $_httpStatus = 500;
}
