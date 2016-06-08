<?php

use \Nf\Registry;
use \Nf\Ini;

class IniTest extends PHPUnit_Framework_TestCase
{
    
    public function testGetValue() {
        $this->assertTrue(!empty(Registry::get('config')->error->displayPHPErrors));
    }
    
    public function testDeepReplace() {
        $iniContent = 'testReplace=aaa';
        $filename = Registry::get('applicationPath') . '/cache/test.' . date('YmdHis') . rand(1, 1000) . '.initmp';
        file_put_contents($filename, $iniContent);
        $iniTest = Ini::parse($filename); 
        $iniReplaced = Ini::deepReplace($iniTest, 'a', 'z');
        unlink($filename);
        $this->assertTrue($iniReplaced->testReplace==='zzz');
    }
    
}
