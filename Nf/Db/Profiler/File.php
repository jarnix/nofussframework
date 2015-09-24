<?php
namespace Nf\Db\Profiler;

class File extends \Nf\Profiler\File
{
    
    public function afterQuery($resource, $sql, $duration)
    {
        fputs($this->handle, date('Y-m-d H:i:s') . PHP_EOL . str_replace(array(
            "\n",
            "\t"
        ), ' ', $sql) . PHP_EOL . round($duration * 10000, 2) . ' ms' . PHP_EOL . '--' . PHP_EOL);
    }
}
