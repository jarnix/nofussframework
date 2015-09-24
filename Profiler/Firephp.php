<?php
namespace Nf\Profiler;

use \Nf\Registry;
use \Nf\Middleware\Post;

class Firephp extends ProfilerAbstract
{
    
    use Post;

    protected $totalDuration = 0;

    protected $firephp = false;
    
    protected $dbName = '';

    public function output()
    {
    }

    public function __construct($config)
    {
        
        require_once(realpath(Registry::get('libraryPath') . '/php/classes/FirePHPCore/FirePHP.class.php'));
        $this->firephp = \FirePHP::getInstance(true);
        
        $this->label = static::LABEL_TEMPLATE;
        
        $front = \Nf\Front::getInstance();
        
        $this->dbName = $config['name'];
        
        $front->registerMiddleware($this);
        
        $this->payload = array(
            array(
                'Duration',
                'Query',
                'Time'
            )
        );
    }
}
