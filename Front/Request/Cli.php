<?php
namespace Nf\Front\Request;

class Cli extends AbstractRequest
{
    
    // cli parameters that are already used by the framework
    const RESERVED_CLI_PARAMS = 'e,environment,l,locale,a,action,m,make';

    protected $_params = array();

    public function __construct($uri)
    {
        $this->_uri = $uri;
    }

    public function getUri()
    {
        return $this->_uri;
    }

    public function isXhr()
    {
        return false;
    }
    
    // sets additional parameters from the command line from the arguments
    public function setAdditionalCliParams()
    {
        $reservedParams = explode(',', self::RESERVED_CLI_PARAMS);
        
        $params = [];
        
        $ac = 1;
        while ($ac < (count($_SERVER['argv']))) {
            $paramName = substr($_SERVER['argv'][$ac], 1);
            if (! in_array($paramName, $reservedParams)) {
                $params[$paramName] = $_SERVER['argv'][$ac + 1];
            }
            $ac += 2;
        }
        
        foreach ($params as $param => $value) {
            $this->setParam($param, $value);
        }
    }

    public function getParams()
    {
        $return = $this->_params;
        return $return;
    }
}
