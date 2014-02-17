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
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        if ($trace[3]['class'] == 'oxWidgetControl') {
            // widget output, which is contained in shopcontrol output
            parent::output($sName, $output);
        } else {
            // regular shop output
            OxidTestDriver::getCurrentInstance()->registerOutput($sName, $output);
        }
    }

}
