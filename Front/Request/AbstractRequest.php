<?php

namespace Nf\Front\Request;

class AbstractRequest
{

    private $_paramSources=array('_GET', '_POST');

    public function setParam($name, $value)
    {
        $this->_params[$name]=$value;
    }

    public function getParams()
    {
        return $this->_params;
    }

    protected function getParamSources()
    {
        return $this->_paramSources;
    }
}
