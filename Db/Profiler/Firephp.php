<?php
namespace Nf\Db\Profiler;

class Firephp extends \Nf\Profiler\Firephp
{

    const LABEL_TEMPLATE = '#dbName# (#nbQueries# @ #totalDuration# ms)';

    public function afterQuery($resource, $sql, $duration)
    {
        $this->payload[] = array(
            '' . round($duration * 10000, 2),
            str_replace(array(
                "\n",
                "\t"
            ), ' ', $sql),
            date('Y-m-d H:i:s')
        );
        
        $this->totalDuration += $duration * 10000;
    }
    
    // outputs the payload
    public function execute()
    {
        $this->label = str_replace(array(
            '#dbName#',
            '#nbQueries#',
            '#totalDuration#'
        ), array(
            $this->dbName,
            count($this->payload) - 1,
            round($this->totalDuration, 2)
        ), $this->label);
        
        $this->firephp->fb(array(
            $this->label,
            $this->payload
        ), \FirePHP::TABLE);
    }
}
