<?php

namespace Nf\Front\Response\Cli;

class FormatTime
{

}

class Timer
{
    public $time;
    function __construct()
    {
        $this->start();
    }
    function start($offset = 0)
    {
        $this->time = microtime(true) + $offset;
    }
    function seconds()
    {
        return microtime(true) - $this->time;
    }
};

// We need this to limit the frequency of the progress bar. Or else it
// hugely slows down the app.
class FPSLimit
{
    public $frequency;
    public $maxDt;
    public $timer;
    function __construct($freq)
    {
        $this->setFrequency($freq);
        $this->timer = new Timer();
        $this->timer->start();
    }
    function setFrequency($freq)
    {
        $this->frequency = $freq;
        $this->maxDt = 1.0/$freq;
    }
    function frame()
    {
        $dt = $this->timer->seconds();
        if ($dt > $this->maxDt) {
            $this->timer->start($dt - $this->maxDt);
            return true;
        }
        return false;
    }
};

class Progress
{
    // generic progress class to update different things
    function update($units, $total)
    {
    }
}

class ProgressBar extends Progress
{
    private $cols;
    private $limiter;
    private $units;
    private $total;
    private $autoCols = false;
    private $first = true;

    function __construct($total = null, $cols = null)
    {
        if ($total != null) {
            $this->total = $total;
        }
        if ($this->cols==null) {
            $this->autoCols = true;
        } else {
            $this->cols = $cols;
        }
        // change the fps limit as needed
        $this->limiter = new FPSLimit(10);
    }

    function __destruct()
    {
        if (!$this->first) {
            $this->draw();
        }
    }

    function updateSize()
    {
        // get the number of columns
        exec("tput cols 2>&1", $out, $ret);
        if ($ret!=0) {
            $this->cols=40;
        } else {
            $this->cols = (int)$out[0];
        }
    }

    function draw()
    {
        if ($this->autoCols) {
            $this->updateSize();
        }
        self::showStatus($this->units, $this->total, $this->cols, $this->cols);
    }

    function update($units, $total = null)
    {
        $this->units = $units;
        if ($total != null) {
            $this->total = $total;
        }
        if (!$this->limiter->frame()) {
            return;
        }
        $this->draw();
    }
    
    private function showStatus($done, $total, $size = 30, $lineWidth = -1)
    {
        
        if ($this->first) {
            echo PHP_EOL;
            $this->first = false;
        }
        
        if ($lineWidth <= 0) {
            $lineWidth = 50;
        }

        static $start_time;

        // to take account for [ and ]
        $size -= 3;
        // if we go over our bound, just ignore it
        if ($done > $total) {
            return;
        }

        if (empty($start_time)) {
            $start_time=time();
        }
        $now = time();

        $perc=(double)($done/$total);

        $bar=1+floor($perc*$size);

        // jump to the begining
        echo "\r";
        // jump a line up
        echo "\x1b[A";

        $status_bar="[";
        
        $status_bar.=str_repeat("=", $bar);
        
        if ($bar<$size) {
            $status_bar.=">";
            $status_bar.=str_repeat(" ", $size-$bar);
        } else {
            $status_bar.="=";
        }

        $disp=number_format($perc*100, 1);

        $status_bar.="]";
        $details = "$disp% - $done/$total ";

        $rate = ($now-$start_time)/$done;
        $left = $total - $done;
        $eta = round($rate * $left, 2);

        $elapsed = $now - $start_time;

        $details .= " ETA: " . self::formatTime($eta)." TOTAL: ". self::formatTime($elapsed) . "   ";

        $lineWidth--;
        if (strlen($details) >= $lineWidth) {
            $details = substr($details, 0, $lineWidth-1);
        }
        echo "$details\n$status_bar";

        // when done, send a newline
        if ($done == $total) {
            echo "\n";
        }
    }
    
    public function done()
    {
        $this->update($this->total, $this->total);
    }
    
     public static function formatTime($sec)
     {
        if ($sec > 100) {
            $sec /= 60;
            if ($sec > 100) {
                $sec /= 60;
                return number_format($sec) . " hr";
            }
            return number_format($sec) . " min";
        }
        return number_format($sec) . " sec";
        }
}
