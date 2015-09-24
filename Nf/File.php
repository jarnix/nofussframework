<?php
namespace Nf;

abstract class File
{

    public static function mkdir($pathname, $mode = 0775, $recursive = false)
    {
        if (! is_dir($pathname)) {
            $oldumask = umask(0);
            $ret = @mkdir($pathname, $mode, $recursive);
            umask($oldumask);
            return $ret;
        }
        return true;
    }

    public static function rename($old, $new, $mode = 0775)
    {
        if (is_readable($old)) {
            $pathname = dirname($new);
            if (! is_dir($pathname)) {
                self::mkdir($pathname, $mode, true);
            }
            return rename($old, $new);
        }
        return false;
    }

    public static function uncompress($src, $dest, $unlinkSrc = false)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $src);
        finfo_close($finfo);
        
        switch ($mimeType) {
            case 'application/x-gzip':
                exec('gzip -dcf ' . $src . ($dest !== null ? ' > ' . $dest : ''), $out, $ret);
                break;
            
            default:
                return false;
        }
        
        if (isset($ret) && $ret === 0) {
            if ($unlinkSrc) {
                @unlink($src);
            }
        }
        return ($ret === 0);
    }

    public static function generatePath($input, $hereMark = '@')
    {
        $input = '' . $input;
        // 15567 => /7/15/56/7/@/
        // 6871985 => /5/68/71/98/5/@/
        // 687198565 /5/68/71/98/56/5/@/
        // 68719856 /6/68/71/98/56/@/
        // 21 /1/21/@/
        // 2121 /1/21/21/@/
        // 1 /1/1/@
        // antix /x/an/ti/x/@/
        $len = strlen($input);
        if ($len == 1) {
            $output = $input . '/' . $input;
        } else {
            $output = $input{$len - 1} . '/';
            for ($i = 0; $i < $len - 1; $i ++) {
                $output .= substr($input, $i, 1);
                if ($i % 2) {
                    $trailing = '/';
                } else {
                    $trailing = '';
                }
                $output .= $trailing;
            }
            $output .= $input{$len - 1};
        }
        $output .= '/' . $hereMark . '/';
        return '/' . $output;
    }
}
