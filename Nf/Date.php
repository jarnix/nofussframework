<?php

namespace Nf;

abstract class Date
{

   
    public static function dateFromMysql($date_origine, $return_time = false)
    {
        $date_output='';
        if ($return_time) {
            // sous la forme 2007-12-25 14:55:36 (datetime) => on renvoie tout reformaté
            if (preg_match('/^(\\d{4})\\-(\\d{2})\\-(\\d{2})\\ (\\d{2}):(\\d{2}):(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/(\\d{4})\\-(\\d{2})\\-(\\d{2})\\ (\\d{2}):(\\d{2}):(\\d{2})/', '$3/$2/$1 $4:$5:$6', $matches[0]);
            } // sous la forme 2007-12-25 (date) => on renvoie une heure 00:00:00
            elseif (preg_match('/^(\\d{4})\\-(\\d{2})\\-(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/(\\d{4})\\-(\\d{2})\\-(\\d{2})/', '$3/$2/$1 00:00:00', $matches[0]);
            } // sous la forme 25/12/2007 14:55:36
            elseif (preg_match('/^(\\d{2})\/(\\d{2})\/(\\d{4}) (\\d{2}):(\\d{2}):(\\d{2})$/', $date_origine, $matches)) {
                $date_output = $date_origine;
            } // sous la forme 25/12/2007 14:55 => on ajoute :00
            elseif (preg_match('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4}) (\\d{2}):(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4}) (\\d{2}):(\\d{2})$/', '$1/$2/$3 $4:$5:00', $matches[0]);
            } // sous la forme 25/12/2007 => on ajoute 00:00:00
            elseif (preg_match('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4})$/', '$1/$2/$3 00:00:00', $matches[0]);
            }
        } else {
            // sous la forme 2007-12-25 (qqch)?
            if (preg_match('/(\\d{4})\\-(\\d{2})\\-(\\d{2})/', $date_origine, $matches)) {
                $date_output = preg_replace('/(\\d{4})\\-(\\d{2})\\-(\\d{2})/', '$3/$2/$1', $matches[0]);
            } // sous la forme 25/12/2007 => on ajoute 00:00:00
            elseif (preg_match('/(\\d{1,2})\/(\\d{1,2})\/(\\d{4})/', $date_origine, $matches)) {
                $date_output = preg_replace('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4})$/', '$1/$2/$3', $matches[0]);
            }
        }
        if ($date_output!='') {
            return $date_output;
        } else {
            throw new \Exception('Erreur date_from_mysql : date non reconnue ' . $date_origine);
        }
    }
    
    public static function dateRange($first, $last, $step = '+1 day')
    {

        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);
        
        switch ($step) {
            case '+1 day':
                $format = 'Y-m-d';
                break;
            case '+1 month':
                $format = 'Y-m-01';
                break;
            case '+1 year':
                $format = 'Y-01-01';
                break;
            default:
                $format = 'Y-m-d';
        }
    
        while ($current <= $last) {
            $dates[] = date($format, $current);
            $current = strtotime($step, $current);
        }
    
        return $dates;
    }

    public static function dateToMysql($date_origine, $return_time = false)
    {

        $date_output='';
        if ($return_time) {
            // sous la forme 25/12/2007 14:55:36 => on reformate tout
            if (preg_match('/^(\\d{2})\/(\\d{2})\/(\\d{4}) (\\d{2}):(\\d{2}):(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/^(\\d{2})\/(\\d{2})\/(\\d{4}) (\\d{2}):(\\d{2}):(\\d{2})$/', '$3-$2-$1 $4:$5:$6', $matches[0]);
            } // sous la forme 25/12/2007 14:55 => on ajoute :00
            elseif (preg_match('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4}) (\\d{2}):(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4}) (\\d{2}):(\\d{2})$/', '$3-$2-$1 $4:$5:00', $matches[0]);
            } // sous la forme 25/12/2007 => on ajoute 00:00:00
            elseif (preg_match('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/^(\\d{1,2})\/(\\d{1,2})\/(\\d{4})$/', '$3-$2-$1 00:00:00', $matches[0]);
            } // sous la forme time() numérique
            elseif (is_numeric($date_origine)) {
                $date_output = date("Y-m-d H:i:s", $date_origine);
            } // sous la forme mysql datetime
            elseif (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2}) (\\d{2}):(\\d{2}):(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/(\\d{4})-(\\d{2})-(\\d{2}) (\\d{2}):(\\d{2}):(\\d{2})/', '$1-$2-$3 $4:$5:$6', $matches[0]);
            } // sous la forme mysql date
            elseif (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/(\\d{4})-(\\d{2})-(\\d{2})/', '$1-$2-$3 00:00:00', $matches[0]);
            }
        } else {
            if (preg_match('/(\\d{1,2})\/(\\d{1,2})\/(\\d{4})/', $date_origine, $matches)) {
                $date_output = preg_replace('/(\\d{1,2})\/(\\d{1,2})\/(\\d{4})/', '$3-$2-$1', $matches[0]);
            } // sous la forme d'une timestamp numérique
            elseif (is_numeric($date_origine)) {
                $date_output = date("Y-m-d", $date_origine);
            } // sous la forme mysql datetime
            elseif (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2}) (\\d{2}):(\\d{2}):(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/(\\d{4})-(\\d{2})-(\\d{2}) (\\d{2}):(\\d{2}):(\\d{2})/', '$1-$2-$3', $matches[0]);
            } // sous la forme mysql date
            elseif (preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', $date_origine, $matches)) {
                $date_output = preg_replace('/(\\d{4})-(\\d{2})-(\\d{2}) (\\d{2}):(\\d{2}):(\\d{2})/', '$1-$2-$3', $matches[0]);
            }
        }
        if ($date_output!='') {
            return $date_output;
        } elseif ($date_output=='') {
            return null;
        } else {
            throw new \Exception('Erreur date_to_mysql : date non reconnue ' . $date_origine);
        }
    }
}
