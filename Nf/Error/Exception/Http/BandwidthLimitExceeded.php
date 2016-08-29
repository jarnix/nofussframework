<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class BandwidthLimitExceeded extends Http
{
    
    public $doLog = false;
    
    protected $_httpStatus = 509;
}
