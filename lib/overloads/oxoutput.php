<?php

class OxidTestDriverOxOutput extends OxidTestDriverOxOutput_parent {

    // new
    private $unitTestOutput = null;
    private static $unitTestCurrentInstance = null;

    // new
    public function unitTestGetOutput() {
        return $this->unitTestOutput;
    }

    public static function unitTestGetCurrentInstance() {
        return self::$unitTestCurrentInstance;
    }

    // overload
    public function __construct() {
        self::$unitTestCurrentInstance = $this;
        parent::__construct();
    }

    // overload
    public function output($sName, $output) {
        OxidTestDriver::getCurrentInstance()->registerOutput($sName, $output);
    }

}
