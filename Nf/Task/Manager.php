<?php
namespace Nf\Task;

class Manager
{

    protected $pool;

    function __construct()
    {
        $this->pool = array();
    }

    function addTask($task, $callbackFunction = null)
    {
        $this->pool[] = array(
            'task' => $task,
            'callback' => $callbackFunction
        );
    }

    function run()
    {
        foreach ($this->pool as $taskInfos) {
            $taskInfos['task']->fork();
        }
        
        while (1) {
            // echo "waiting\n";
            $pid = pcntl_wait($extra);
            if ($pid == - 1) {
                break;
            }
            // echo ": task done : $pid\n";
            $this->finishTask($pid);
        }
        // echo "processes done ; exiting\n";
        return;
    }

    function finishTask($pid)
    {
        $taskInfos = $this->pidToTaskInfos($pid);
        if ($taskInfos) {
            $taskInfos['task']->finish();
            $taskInfos['callback']();
        }
    }

    function pidToTaskInfos($pid)
    {
        foreach ($this->pool as $taskInfos) {
            if ($taskInfos['task']->pid() == $pid)
                return $taskInfos;
        }
        return false;
    }
}