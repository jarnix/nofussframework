<?php
namespace Nf\Profiler;

class File extends ProfilerAbstract
{

    protected $handle = null;
    
    protected $filepath = '/tmp/profiler.log';

    public function __construct($config)
    {
        if (isset($config['file'])) {
            $this->filepath = $config['file'];
        }
        $this->handle = fopen($this->filepath, 'a');
    }
}
