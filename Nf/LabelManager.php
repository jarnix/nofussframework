<?php

namespace Nf;

class LabelManager extends Singleton
{

    protected static $_instance;

    private $labelsLoaded=false;
    private $labels=array();

    // load the labels
    public function loadLabels($locale, $force = false)
    {
        if (!$this->labelsLoaded || $force) {
            if (file_exists(\Nf\Registry::get('applicationPath') . '/labels/' . $locale . '.ini')) {
                $this->labels=parse_ini_file(\Nf\Registry::get('applicationPath') . '/labels/' . $locale . '.ini', true);
                $this->labelsLoaded=true;
            } else {
                throw new \Exception('Cannot load labels for this locale (' . $locale . ')');
            }
        }
    }

    public static function get($lbl)
    {
        $instance=self::$_instance;
        return (isset($instance->labels[$lbl])) ? $instance->labels[$lbl] : '' ;
    }

    public static function getAll($section = null)
    {
        $instance=self::$_instance;
        if ($section!=null) {
            return $instance->labels[$section];
        } else {
            return $instance->labels;
        }
    }

    public function __get($lbl)
    {
        return $this->labels[$lbl];
    }
}
