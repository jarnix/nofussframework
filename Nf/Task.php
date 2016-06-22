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
        if ($pid == - 1) {
            throw new Exception('fork error on Task object');
        } elseif ($pid) {
            // we are in the parent class
            $this->pid = $pid;
            // echo "< in parent with pid {$this->pid}\n";
        } else {
            // we are in the child ᶘ ᵒᴥᵒᶅ
            $this->ppid = posix_getppid();
            $this->pid = posix_getpid();

            $callbackParams = $this->run();
            $this->setCallbackParams( $callbackParams );

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

    public function getCallbackParams()
    {
        $shmId = @shmop_open($this->pid, 'a', 0644, 0);
        if ( empty($shmId) ) {
            return false;
        }

        $datas = unserialize( shmop_read(
            $shmId, 0, shmop_size($shmId)
        ));
        shmop_delete($shmId);
        shmop_close($shmId);

        return $datas;
    }

    protected function setCallbackParams( $callbackParams )
    {
        if ( empty($callbackParams) ) {
            return false;
        }

        $strDatas = serialize( $callbackParams );

        $shmId = shmop_open(
            $this->pid,
            'c',
            0644,
            strlen($strDatas)
        );

        if ( !$shmId ) {
            echo 'Couldn\'t create shared memory segment' . PHP_EOL;

            return false;
        }

        $sizeWritten = shmop_write($shmId, $strDatas, 0);

        if( $sizeWritten != strlen($strDatas) ) {
            echo 'Couldn\'t write shared memory data'.PHP_EOL;
            return false;
        }

        return true;
    }

}
