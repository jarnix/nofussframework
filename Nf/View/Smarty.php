<?php
namespace Nf\View;

use Nf\View;

class Smarty extends View
{

    protected static $_instance;

    private $_smarty = null;

    const FILE_EXTENSION = '.tpl';

    private $_vars = array();

    protected function __construct()
    {
        $this->_smarty = new \Smarty();
        $front = \Nf\Front::getInstance();
        $this->setBasePath($front->getModuleName());
    }

    /**
     * Return the template engine object, if any
     *
     * @return mixed
     */
    public function getEngine()
    {
        return $this->_smarty;
    }

    public function configLoad($filepath, $section = null)
    {
        $lang = \Nf\Registry::getInstance()->get('lang');
        $config_path = realpath(Registry::get('applicationPath') . '/configs/' . $lang . '/' . $front->getModuleName() . '/' . $filepath);
        $this->_smarty->config_load($config_path, $section);
    }

    /**
     * Assign a variable to the view
     *
     * @param string $key
     *            The variable name.
     * @param mixed $val
     *            The variable value.
     * @return void
     */
    public function __set($key, $val)
    {
        if ('_' == substr($key, 0, 1)) {
            throw new Exception('Setting private var is not allowed', $this);
        }
        if ($this->_smarty == null) {
            throw new Exception('Smarty is not defined', $this);
        }
        $this->_smarty->assignByRef($key, $val);
        return;
    }

    public function __get($key)
    {
        if ('_' == substr($key, 0, 1)) {
            throw new Exception('Setting private var is not allowed', $this);
        }
        if ($this->_smarty == null) {
            throw new Exception('Smarty is not defined', $this);
        }
        return $this->_smarty->getTemplateVars($key);
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
        $vars = $this->_smarty->getTemplateVars();
        return isset($vars[$key]);
    }

    /**
     * Allows unset() on object properties to work
     *
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        $this->_smarty->clearAssign($key);
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
        $this->_smarty->clearAllAssign();
    }

    /**
     * Processes a view script and returns the output.
     *
     * @param string $name
     *            The script script name to process.
     * @return string The script output.
     */
    public function render($name)
    {
        $this->_response->addBodyPart($this->fetch($name));
    }

    public function fetch($name)
    {
        return $this->_smarty->fetch($name . self::FILE_EXTENSION);
    }

    public function setBasePath($path)
    {
        $config = \Nf\Registry::get('config');
        
        // configuration de Smarty
        $this->_smarty->setTemplateDir(array(
            \Nf\Registry::get('applicationPath') . '/application/' . \Nf\Registry::get('version') . '/' . $path . '/views/',
            \Nf\Registry::get('libraryPath') . '/php/application/' . \Nf\Registry::get('version') . '/' . $path . '/views/'
        ));
        
        // répertoire du cache Smarty
        $cacheDirectory = realpath(\Nf\Registry::get('applicationPath')) . '/cache/smarty/' . \Nf\Registry::get('version') . '/' . \Nf\Registry::get('locale') . '/' . $path . '/';
        // répertoire des templates compilés
        $compileDirectory = realpath(\Nf\Registry::get('applicationPath')) . '/cache/templates_c/' . \Nf\Registry::get('version') . '/' . \Nf\Registry::get('locale') . '/' . $path . '/';
        
        $configDirectory = realpath(\Nf\Registry::get('applicationPath')) . '/configs/' . \Nf\Registry::get('version') . '/' . \Nf\Registry::get('locale') . '/' . $path . '/';
        
        $pluginsDirectories = array(
            realpath(\Nf\Registry::get('applicationPath') . '/plugins/'),
            realpath(\Nf\Registry::get('libraryPath') . '/php/plugins/'),
            realpath(\Nf\Registry::get('libraryPath') . '/php/classes/Smarty/plugins/')
        );
        
        \Nf\File::mkdir($cacheDirectory, 0755, true);
        \Nf\File::mkdir($compileDirectory, 0755, true);
        
        $this->_smarty->setUseSubDirs(true);
        
        // répertoire de cache de smarty
        $this->_smarty->setCacheDir($cacheDirectory);
        // répertoire de compilation
        $this->_smarty->setCompileDir($compileDirectory);
        // répertoire des configs smarty des applis
        $this->_smarty->setConfigDir($configDirectory);
        // répertoire des plugins
        foreach ($pluginsDirectories as $pluginsDirectory) {
            $this->_smarty->addPluginsDir($pluginsDirectory);
        }
        
        $this->_smarty->left_delimiter = $config->view->smarty->leftDelimiter;
        $this->_smarty->right_delimiter = $config->view->smarty->rightDelimiter;
        
        // dev : we disable Smarty's caching
        if (\Nf\Registry::get('environment') == 'dev') {
            $this->_smarty->caching = false;
            $this->_smarty->force_compile = true;
            $this->_smarty->setCompileCheck(true);
        }
        
        // only one file generated for each rendering
        $this->_smarty->merge_compiled_includes = true;
        
        // send the registry to the view
        $this->_smarty->assign('_registry', \Nf\Registry::getInstance());
        
        // send the label Manager to the view
        $this->_smarty->assign('_labels', \Nf\LabelManager::getInstance());
        
        // $this->_smarty->testInstall();
    }
}
