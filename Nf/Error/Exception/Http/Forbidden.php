<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class Forbidden extends Http
{
    
    public $doLog = false;
    
    protected $_httpStatus = 403;
}
