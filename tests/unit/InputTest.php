<?php

use \Nf\Registry;
use \Nf\Input;

class InputTest extends PHPUnit_Framework_TestCase
{
    
    const CONFIG_CLASS = 'Input';
    const PARAM_ID = 'id';
    const PARAM_EMAIL = 'email';
    const PARAM_FIRSTNAME = 'firstname';
    const PARAM_LASTNAME = 'lastname';
    const PARAM__LASTNAME = '_lastname';
    const PARAM_URL = 'url';
    const PARAM_BASE64 = 'myBase64';
    const PARAM_AGE = 'age';

    /**
     * __construct
     * 
     * @param string $name
     * @param array $data
     * @param string $dataName 
     */
    public function __construct($name = null, array $data = array(), $dataName = '') {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(){}

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(){}
    
    /**
     * _getInput
     * 
     * @param array $p
     * @param array $f
     * @param array $v
     * @return \Input
     */
    protected function _getInput($p = array(), $f = array(), $v = array()) {
        return new Input($p, $f, $v);
    }
    
    /**
     * testInstance
     * 
     */
    public function testInstance()
    {
        $this->assertTrue($this->_getInput() instanceof Input);
    }    
 
    /**
     * getValidatorsNoFiltersProvider
     * 
     * @return array 
     */
    public function getValidatorsNoFiltersProvider() {
        $p1 = $this->_getTestParamsCase1();
        $v1 = $this->_getTestValidatorsCase1($p1);

        $f1 = array();
        return array(
            array($p1, $f1, $v1, true)                              // No filters
            , array($p1, $this->_getTestFilterCase2(), $v1, true)   // filters
        );
    }

    /**
     * testInputValidatorsNoFilters
     * 
     * @param array $p
     * @param array $f
     * @param array $v
     * @param boolean $expected
     * 
     * @dataProvider getValidatorsNoFiltersProvider
     */
    public function testInputValidatorsNoFilters($p, $f, $v, $expected) {
        $this->assertEquals(
            $this->_getInput($p, $f, $v)->isValid()
            , $expected
        );
    }
    
    /**
     * _getTestParamsCase1
     * 
     * @return array
     */
    private function _getTestParamsCase1() {
        return array(
            self::PARAM_ID => 100
            , self::PARAM_FIRSTNAME => 'peter'
            , self::PARAM_LASTNAME => 'cheese'
            , self::PARAM__LASTNAME => 'cheese'
            , self::PARAM_EMAIL => 'peter.cheesse@peter.cheese.tld'
            , self::PARAM_URL => 'http://user:password@sub.domain.tld/m/c/a/id/21'
            , self::PARAM_BASE64 => 'cGV0ZXIgY2hlZXNl'
            , self::PARAM_AGE => 44
        );
    }
    
    /**
     * _getTestValidatorsCase1
     * 
     * @param array $p
     * @return array
     */
    private function _getTestValidatorsCase1($p) {
        $idValue = $p[self::PARAM_ID];
        $firstnameLen = strlen($p[self::PARAM_FIRSTNAME]);
        $lastnameLen = strlen($p[self::PARAM_LASTNAME]);
        $ageValue = $p[self::PARAM_AGE];
        return array(
            self::PARAM_ID => array(
                Input::V_NUMERIC
                , Input::V_REQUIRED
                , array(Input::V_GREATERTHAN, $idValue)        // ?
                , array(Input::V_LESSTHAN, $idValue)           // ?
                , array(Input::V_EQUALS, $idValue)
            )
            , self::PARAM_FIRSTNAME => array(
                Input::V_ALPHA
                , Input::V_REQUIRED
                , array(Input::V_MAXLENGTH, $firstnameLen)
                , array(Input::V_MINLENGTH, $firstnameLen)
                , array(Input::V_EXACTLENGTH, $firstnameLen)
                , array(Input::V_REGEXP, '/^p.*r$/')            // begin p end r
                , array(Input::V_REGEXP, '/^[a-z]{5}$/')        // strict len  5
                , array(Input::V_REGEXP, '/^[a-z]{5,5}$/')      // range len 5,5
                , array(Input::V_REGEXP, '/^[a-z]{5,}$/')       // min len 5
            )
            , self::PARAM_LASTNAME => array(
                Input::V_ALPHA
                , Input::V_REQUIRED
                , array(Input::V_MINLENGTH, $lastnameLen)
                , array(Input::V_MAXLENGTH, $lastnameLen)
                , array(Input::V_EXACTLENGTH, $lastnameLen)
                , array(Input::V_REGEXP, '/^c.*e$/')            // begin c end e
                , array(Input::V_REGEXP, '/^[a-z]{6}$/')        // strict len  6
                , array(Input::V_REGEXP, '/^[a-z]{5,6}$/')      // range len 5,6
                , array(Input::V_REGEXP, '/^[a-z]{6,}$/')       // min len 6
            )
            , self::PARAM__LASTNAME => array(
                Input::V_ALPHA
                , Input::V_REQUIRED
                , array(Input::V_MATCHES, self::PARAM_LASTNAME)
            )
            , self::PARAM_EMAIL => array(
                Input::V_REQUIRED
                , Input::V_EMAIL
                , array(Input::V_REGEXP, $this->getRfc5322Regexp())
            )
            , self::PARAM_URL => array(
                Input::V_REQUIRED
                , Input::V_URL
            )
            , self::PARAM_BASE64 => array(
                Input::V_REQUIRED
                , Input::V_BASE64
            )
            , self::PARAM_AGE => array(
                Input::V_NUMERIC
                , Input::V_REQUIRED
                , array(Input::V_GREATERTHAN, $ageValue - 1)        // greather than age - 1
                , array(Input::V_LESSTHAN, $ageValue + 1)           // less than age - 1
                , array(Input::V_EQUALS, $ageValue)                 // equals to itself
            )
        );
    }
    
    /**
     * _getTestFilterCase2
     * 
     * @return array
     */
    private function _getTestFilterCase2() {
        return array(
            self::PARAM_ID => array(
                Input::F_NUMERIC
            )
            , self::PARAM_FIRSTNAME => array(
                Input::F_ALPHA
            )
            , self::PARAM_LASTNAME => array(
                Input::F_ALPHA
            )
            , self::PARAM__LASTNAME => array(
                Input::F_ALPHA
            )
            , self::PARAM_EMAIL => array()
            , self::PARAM_URL => array(
                Input::F_URL
            )
            , self::PARAM_BASE64 => array(
                Input::F_BASE64
            )
            , self::PARAM_AGE => array(
                Input::F_NUMERIC
            )
        );
    }

    /**
     * getRfc5322Regexp
     * 
     * @see http://emailregex.com/
     * @return string
     */
    private function getRfc5322Regexp() {
        return '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?))'
            . '{255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|'
            . '(?:\x22?[^\x5C\x22]\x22?)){65,}@)'
            . '(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)'
            . '|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|'
            . '(?:\x5C[\x00-\x7F]))*\x22))'
            . '(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|'
            . '(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|'
            . '(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?'
            . '[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|'
            . '(?:(?:xn--)[a-z0-9]+))'
            . '(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|'
            . '(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})'
            . '?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|'
            . '(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)'
            . '|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})'
            . '?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|'
            . '(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))'
            . '(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|'
            . '(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';
    }
}
