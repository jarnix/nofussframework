<?php
namespace Nf;

/**
 * Reads an .ini file, handling sections, overwriting...
 *
 * @author Julien Ricard
 * @package Nf
 */
class Ini
{

    /**
     * Internal storage array
     *
     * @var array
     */
    private static $result = array();

    /**
     * Loads in the ini file specified in filename, and returns the settings in
     * it as an associative multi-dimensional array
     *
     * @param string $filename
     *            The filename of the ini file being parsed
     * @param boolean $process_sections
     *            By setting the process_sections parameter to TRUE,
     *            you get a multidimensional array, with the section
     *            names and settings included. The default for
     *            process_sections is FALSE
     * @param string $section_name
     *            Specific section name to extract upon processing
     * @throws Exception
     * @return array|boolean
     */
    public static function parse($filename, $processSections = false, $sectionName = null, $fallbackSectionName = null, $toObject = true)
    {
        
        // load the raw ini file
        // automatically caches the ini file if an opcache is present
        ob_start();
        include($filename);
        $str = ob_get_contents();
        ob_end_clean();
        $ini = parse_ini_string($str, $processSections);
        
        // fail if there was an error while processing the specified ini file
        if ($ini === false) {
            return false;
        }
        
        // reset the result array
        self::$result = array();
        
        if ($processSections === true) {
            // loop through each section
            foreach ($ini as $section => $contents) {
                // process sections contents
                self::processSection($section, $contents);
            }
        } else {
            // treat the whole ini file as a single section
            self::$result = self::processSectionContents($ini);
        }
        
        // extract the required section if required
        if ($processSections === true) {
            if ($sectionName !== null) {
                // return the specified section contents if it exists
                if (isset(self::$result[$sectionName])) {
                    if ($toObject) {
                        return self::bindArrayToObject(self::$result[$sectionName]);
                    } else {
                        return self::$result[$sectionName];
                    }
                } else {
                    if ($fallbackSectionName !== null) {
                        if ($toObject) {
                            return self::bindArrayToObject(self::$result[$fallbackSectionName]);
                        } else {
                            return self::$result[$fallbackSectionName];
                        }
                    } else {
                        throw new \Exception('Section ' . $sectionName . ' not found in the ini file');
                    }
                }
            }
        }
        
        // if no specific section is required, just return the whole result
        if ($toObject) {
            return self::bindArrayToObject(self::$result);
        } else {
            return self::$result;
        }
    }

    /**
     * Process contents of the specified section
     *
     * @param string $section
     *            Section name
     * @param array $contents
     *            Section contents
     * @throws Exception
     * @return void
     */
    private static function processSection($section, array $contents)
    {
        // the section does not extend another section
        if (stripos($section, ':') === false) {
            self::$result[$section] = self::processSectionContents($contents);
            
            // section extends another section
        } else {
            // extract section names
            list ($ext_target, $ext_source) = explode(':', $section);
            $ext_target = trim($ext_target);
            $ext_source = trim($ext_source);
            
            // check if the extended section exists
            if (! isset(self::$result[$ext_source])) {
                throw new \Exception('Unable to extend section ' . $ext_source . ', section not found');
            }
            
            // process section contents
            self::$result[$ext_target] = self::processSectionContents($contents);
            
            // merge the new section with the existing section values
            self::$result[$ext_target] = self::arrayMergeRecursive(self::$result[$ext_source], self::$result[$ext_target]);
        }
    }

    /**
     * Process contents of a section
     *
     * @param array $contents
     *            Section contents
     * @return array
     */
    private static function processSectionContents(array $contents)
    {
        $result = array();
        
        // loop through each line and convert it to an array
        foreach ($contents as $path => $value) {
            // convert all a.b.c.d to multi-dimensional arrays
            $process = self::processContentEntry($path, $value);
            
            // merge the current line with all previous ones
            $result = self::arrayMergeRecursive($result, $process);
        }
        
        return $result;
    }

    /**
     * Convert a.b.c.d paths to multi-dimensional arrays
     *
     * @param string $path
     *            Current ini file's line's key
     * @param mixed $value
     *            Current ini file's line's value
     * @return array
     */
    private static function processContentEntry($path, $value)
    {
        $pos = strpos($path, '.');
        
        if ($pos === false) {
            return array(
                $path => $value
            );
        }
        
        $key = substr($path, 0, $pos);
        $path = substr($path, $pos + 1);
        
        $result = array(
            $key => self::processContentEntry($path, $value)
        );
        
        return $result;
    }

    public static function bindArrayToObject($array)
    {
        $return = new \StdClass();
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $return->$k = self::bindArrayToObject($v);
            } else {
                $return->$k = $v;
            }
        }
        return $return;
    }

    /**
     * Merge two arrays recursively overwriting the keys in the first array
     * if such key already exists
     *
     * @param mixed $a
     *            Left array to merge right array into
     * @param mixed $b
     *            Right array to merge over the left array
     * @return mixed
     */
    private static function arrayMergeRecursive($a, $b, $level = 0)
    {
        // merge arrays if both variables are arrays
        if (is_array($a) && is_array($b)) {
            // loop through each right array's entry and merge it into $a
            foreach ($b as $key => $value) {
                if (isset($a[$key])) {
                    if (is_int($key) && ! is_array($value)) {
                        $a[] = $value;
                    } else {
                        $a[$key] = self::arrayMergeRecursive($a[$key], $value, $level + 1);
                    }
                } else {
                    if ($key === 0) {
                        $a = array(
                            0 => self::arrayMergeRecursive($a, $value, $level + 1)
                        );
                    } else {
                        $a[$key] = $value;
                    }
                }
            }
        } else {
            // at least one of values is not an array
            $a = $b;
        }
        return $a;
    }

    /**
     * Replaces text in every value of the config, recursively
     *
     * @param string $search
     *            The string to search for
     * @param string $replace
     *            The string for replace with
     * @throws Exception
     * @return Ini
     */
    public static function deepReplace($ini, $search, $replace)
    {
        if (is_object($ini)) {
            foreach ($ini as $key => &$value) {
                $value = self::deepReplace($value, $search, $replace);
            }
        } else {
            $ini = str_replace($search, $replace, $ini);
            return $ini;
        }
        return $ini;
    }
}
