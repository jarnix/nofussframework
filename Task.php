<?php
namespace Nf;

class Task
{

    protected $pid;

    protected $ppid;

    protected $params = null;

    function __construct($params = null)
    {
        $this->params = $params;
    }

    function fork()
    {
        $pid = pcntl_fork();
        if ($pid == - 1)
            throw new Exception('fork error on Task object');
        elseif ($pid) {
            // we are in the parent class
            $this->pid = $pid;
            // echo "< in parent with pid {$this->pid}\n";
        } else {
            // we are in the child ᶘ ᵒᴥᵒᶅ
            $this->ppid = posix_getppid();
            $this->pid = posix_getpid();
            $this->run();
            exit(0);
        }
    }
    
    // overload this method in your class
    function run()
    {
        // echo "> in child {$this->pid}\n";
    }
    
    // call when a task is finished (in parent)
    function finish()
    {
        // echo "task finished {$this->pid}\n";
    }

    function pid()
    {
        return $this->pid;
    }
}



