<?php

namespace Nf;

abstract class View extends Singleton
{

    protected static $_instance;

    private $_vars=array();

    private $_templateDirectory=null;
    private $_configDirectory=null;

    protected $_response;

    public function setResponse($response)
    {
        $this->_response=$response;
    }

    public static function factory($name)
    {
        $className='\\Nf\\View\\' . ucfirst($name);
        $view=$className::getInstance();
        return $view;
    }
}
