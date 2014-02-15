<?php

class oxUtilsObject extends oxUtilsObject_Original {

    // new
    protected static $unitTestInstance;
    protected static $unitTestOverloads;

    // new
    public function unitTestResetState() {
        $this->_aClassNameCache = array();
        self::$_aLoadedArticles = array();
        self::$_aInstanceCache = array();
        self::$_aModuleVars = array();
        self::$_aClassInstances = array();

        self::$unitTestInstance = null;
        self::$unitTestOverloads = array();
    }

    // new
    public static function unitTestAddOverload($class) {
        self::$unitTestOverloads []= $class;
    }

    // overload
    public static function getInstance() {
        if (!self::$unitTestInstance instanceof oxUtilsObject) {
            $oUtilsObject = new oxUtilsObject();
            self::$unitTestInstance = $oUtilsObject->oxNew('oxUtilsObject');
        }
        return self::$unitTestInstance;
    }

    // overload
    public function getClassName($class) {
        $result = parent::getClassName($class);
        if (in_array($class, self::$unitTestOverloads)) {
            class_alias($class, 'TestDriver' . $class . '_parent');
            require_once __DIR__ . '/../overloads/' . $class . '.php';
            return "TestDriver$class";
        }
        return $result;
    }

}
