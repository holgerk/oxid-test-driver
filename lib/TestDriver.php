<?php

class TestDriver {

    private static $configured = false;
    private static $shopDirectory = null;
    private static $currentInstance = null;
    private static $loadedClasses = array();

    public static function configure($shopDirectory) {
        if (self::$configured) {
            return;
        }
        self::$configured = true;
        self::$shopDirectory = $shopDirectory;

        if (class_exists('oxutilsobject', false)) {
            throw new Exception(
                "Oxid's bootstrap.php allready loaded!\n" .
                "Please call TestDriver::configure(<shopDirectory>) first."
            );
        }

        spl_autoload_register(array(__CLASS__, 'autoload'), true, true);
        include self::$shopDirectory . '/bootstrap.php';
    }

    public static function autoload($class) {
        if (strtolower($class) == 'oxutilsobject') {
            eval('?>' . str_replace(
                'class oxUtilsObject',
                'class oxUtilsObject_Original',
                file_get_contents(self::$shopDirectory . '/core/oxutilsobject.php')));
            require_once __DIR__ . '/replacements/oxutilsobject.php';

            self::configureOverloads();
        }
        self::$loadedClasses []= $class;
    }

    private static function configureOverloads() {
        oxUtilsObject::unitTestAddOverload('oxoutput');
        oxUtilsObject::unitTestAddOverload('oxutils');
        oxUtilsObject::unitTestAddOverload('oxutilsserver');
    }

    public static function getLoadedClasses() {
        return self::$loadedClasses;
    }

    public static function getCurrentInstance() {
        return self::$currentInstance;
    }


    // ========================================================================

    private $output = '';

    public function __construct() {
        if (!self::$configured) {
            throw new Exception(
                "No patches applied!\n" .
                "Please call TestDriver::configure(<shopDirectory>) first."
            );
        }
        self::$currentInstance = $this;
    }

    public function get($params) {
        $this->reset();
        if (is_string($params)) {
            parse_str($params, $result);
            $_GET = $result;
        } else {
            $_GET = $params;
        }
        $this->populateRequestVars();

        Oxid::run();

        return $this->createResponse();
    }

    public function getTitleTag() {
        preg_match('%<title>([^<]+)</title>%i', $this->output, $matches);
        if (empty($matches)) {
            throw new Exception('No title tag found!');
        }
        return $matches[1];
    }


    // ========================================================================

    private function createResponse() {
        $response = new StdClass;
        $response->controller = oxRegistry::getConfig()->getActiveView();
        $response->titleTag = $this->getTitleTag();
        $response->html = $this->output;
        return $response;
    }

    private function reset() {
        $this->output = '';
    }

    private function populateRequestVars() {
        foreach ($_GET as $k => $v) {
            $_REQUEST[$k] = $v;
        }
    }


    // ========================================================================

    // for oxoutput overload
    public function registerOutput($name, $data) {
        $this->output .= $data;
    }

    // for oxutils overload
    public function registerHeader($header) {
    }

    // for oxutils overload
    public function registerRedirect($url, $code) {
        throw new Exception("Got unexpected redirect: $code $url");
        // var_dump(func_get_args());
    }

    // for oxutils overload
    public function registerShowMessageAndExit($message) {
    }



}

