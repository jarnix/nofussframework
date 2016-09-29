<?php

namespace Nf\View;

use Nf\View;
use Nf\Front;
use Nf\Registry;
use Nf\Ini;

class Php extends View
{

    protected static $_instance;

    const FILE_EXTENSION='.php';

    private $_vars=array();

    private $_templateDirectory=null;
    private $_configDirectory=null;

    protected $_response;

    protected function __construct()
    {
        parent::__construct();
        $front=Front::getInstance();
        $this->setBasePath($front->getModuleName());
        // send the label Manager to the view
        $this->_vars['labels'] = \Nf\LabelManager::getInstance();
    }

    /**
     * Assign a variable to the view
     *
     * @param string $key The variable name.
     * @param mixed $val The variable value.
     * @return void
     */
    public function __set($key, $val)
    {
        $this->_vars[$key]=$val;
        return;
    }

    public function __get($key)
    {
        return $this->_vars[$key];
    }

    /**
     * Allows testing with empty() and
     * isset() to work
     *
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->_vars[$key]);
    }

    /**
     * Allows unset() on object properties to work
     *
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->_vars[$key]);
    }

    /**
     * Clear all assigned variables
     *
     * Clears all variables assigned to
     * Zend_View either via {@link assign()} or
     * property overloading ({@link __get()}/{@link __set()}).
     *
     * @return void
     */
    public function clearVars()
    {
        $this->_vars=array();
    }

    /**
     * Processes a view script and returns the output.
     *
     * @param string $name The script script name to process.
     * @return string The script output.
     */
    public function render($name)
    {
        $this->_response->addBodyPart($this->fetch($name));
    }

    public function fetch($name)
    {
        // ob_start, require du tpl, ob_get_contents et return
        extract($this->_vars);
        ob_start();
        include($this->_templateDirectory . $name . self::FILE_EXTENSION);
        $content=ob_get_contents();
        ob_end_clean();
        return $content;
    }


    public function setBasePath($path)
    {
        $this->_templateDirectory = Registry::get('applicationPath') . '/application/' . Registry::get('version') . '/' . $path . '/views/';
        $this->_configDirectory = Registry::get('applicationPath') . '/configs/' . Registry::get('version') . '/' . Registry::get('locale') . '/' . $path . '/';
    }

    public function configLoad($filepath, $section = null)
    {
        // lire le fichier ini, ajouter aux variables
        $ini = Ini::parse($filepath);
        foreach ($ini as $key => $value) {
            $this->_vars[$key]=$value;
        }

    }
}
