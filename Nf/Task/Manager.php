<?php
namespace Nf\Task;

class Manager
{
    
    protected $pool;
    
    // 0 = infinite
    protected $maxThreads = 0;
    
    protected $numberOfRunningThreads = 0;
    protected $numberOfThreadsToLaunch = 0;
    
    protected $numberOfTasks = 0;
    
    
    public function __construct()
    {
        $this->pool = array();
        \Nf\Db::$_forceStoreConnectionInInstance = false;
    }
    
    
    /**
     * Limit the number of simultaneous task, default is 0 = infinite (can be dangerous if you have thousands tasks)
     *  $manager->setMaxThreads(\Nf\Task\Manager::getNumberCPUs() * 2);
     *  is a good idea
     * @param int $maxThreads
     */
    public function setMaxThreads($maxThreads)
    {
        $this->maxThreads = floor(abs($maxThreads));
    }
    
    
    public function addTask($task, $callbackFunction = null)
    {
        $this->pool[] = array(
            'task' => $task,
            'callback' => $callbackFunction
        );
        $this->numberOfTasks++;
    }

    public function getNumberOfThreadsToLaunch()
    {
        return $this->numberOfThreadsToLaunch;
    }
    
    
    /**
     * Fork next task
     * @return bool True if a task was launch, false if all tasks are already launched
     */
    protected function launchNextTask()
    {
        if (! $this->numberOfThreadsToLaunch) {
            return false;
        }
        
        $taskInfos = $this->pool[$this->numberOfTasks - $this->numberOfThreadsToLaunch];
        
        $taskInfos['task']->fork();
        $this->numberOfRunningThreads++;
        $this->numberOfThreadsToLaunch--;
        
        return true;
    }
    
    
    /**
     * Fork all tasks at once
     */
    protected function launchAllTasks()
    {
        while ($this->launchNextTask()) {
        }
    }
    
    
    public function run()
    {
        $this->numberOfThreadsToLaunch = $this->numberOfTasks;
        
        if ($this->maxThreads == 0 || count($this->pool) <= $this->maxThreads) {
            $this->launchAllTasks();
        } else {
            while ($this->numberOfRunningThreads < $this->maxThreads) {
                $this->launchNextTask();
            }
        }
        
        while (1) {
            // echo "waiting\n";
            $pid = pcntl_wait($extra);
            if ($pid == - 1) {
                break;
            }
            // echo ": task done : $pid\n";
            $this->finishTask($pid);
            
            if ($this->numberOfThreadsToLaunch) {
                $this->launchNextTask();
            }
        }
        // echo "processes done ; exiting\n";
        return;
    }
    
    
    protected function finishTask($pid)
    {
        $taskInfos = $this->pidToTaskInfos($pid);
        if (!$taskInfos) {
            return;
        }

        $taskInfos['task']->finish();

        if (!empty($taskInfos['callback'])) {
            $taskInfos['callback'](
                $taskInfos['task']->getCallbackParams()
            );
        }

        $this->numberOfRunningThreads--;
    }
    
    
    protected function pidToTaskInfos($pid)
    {
        foreach ($this->pool as $taskInfos) {
            if ($taskInfos['task']->pid() == $pid) {
                return $taskInfos;
            }
        }
        return false;
    }
    
    
    /**
     * @see https://gist.github.com/ezzatron/1321581
     */
    public static function getNumberCPUs()
    {
        $numCpus = 1;
        if (is_file('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            $numCpus = count($matches[0]);
        } elseif ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
            $process = @popen('wmic cpu get NumberOfCores', 'rb');
            if (false !== $process) {
                fgets($process);
                $numCpus = intval(fgets($process));
                pclose($process);
            }
        } else {
            $process = @popen('sysctl -a', 'rb');
            if (false !== $process) {
                $output = stream_get_contents($process);
                preg_match('/hw.ncpu: (\d+)/', $output, $matches);
                if ($matches) {
                    $numCpus = intval($matches[1][0]);
                }
                pclose($process);
            }
        }
        return $numCpus;
    }
}
