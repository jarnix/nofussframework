<?php
namespace Nf\Error\Exception\Http;

use Nf\Error\Exception\Http;

class NoContent extends Http
{
    public $doLog = false;

    protected $_httpStatus = 204;

    public function getErrors()
    {
        return '';
    }
}
