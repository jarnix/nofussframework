<?php
namespace Nf\Error\Logger;

use \Nf\Registry;

class Syslog
{

    public function log($err)
    {
        if (!is_string($err['message'])) {
            $err['message'] = print_r($err['message'], true);
        }
        syslog(LOG_WARNING, 'error in file: ' . $err['file'] . ' (line ' . $err['line'] . '). ' . $err['message']);
    }
}
