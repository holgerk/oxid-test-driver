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
        $class = strtolower($class);

        $replacements = array(
            'oxutilsobject' => array(
                'search'  => 'class oxUtilsObject',
                'replace' => 'class oxUtilsObject_Original'),
            'oxregistry'    => array(
                'search'  => 'class oxRegistry',
                'replace' => 'class oxRegistry_Original'),
            'oxsupercfg'    => array(
                'search'  => 'class oxSuperCfg',
                'replace' => 'class oxSuperCfg_Original'),
        );

        if (isset($replacements[$class])) {
            // maybe we should write the replacments to a temporary file for better stacktraces
            eval('?>' . str_replace(
                $replacements[$class]['search'],
                $replacements[$class]['replace'],
                file_get_contents(self::$shopDirectory . "/core/$class.php")));
            require_once __DIR__ . "/replacements/$class.php";
        }

        if ($class == 'oxutilsobject') {
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
    private $cookies = array();

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
        return $this->request('GET', $params);
    }

    public function post($params) {
        return $this->request('POST', $params);
    }

    public function request($method, $params) {

        $method = strtoupper($method);

        if (is_string($params)) {
            parse_str($params, $result);
            $GLOBALS["_$method"] = $result;
        } else {
            $GLOBALS["_$method"] = $params;
        }
        $this->populateRequestVars();

        $_SERVER['REQUEST_METHOD'] = $method;

        Oxid::run();
        $response = $this->createResponse();
        $this->reset();


        return $response;
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
        $response->user       = $response->controller->getUser();
        $response->titleTag   = $this->getTitleTag();
        $response->html       = $this->output;
        $response->sessionId  = (isset($this->cookies['sid'])) ? $this->cookies['sid'] : null;
        return $response;
    }

    private function reset() {
        $_GET = array();
        $this->output = '';
        $this->cookies = array();

        oxUtilsObject::getInstance()->unitTestReset();
        oxSuperCfg::unitTestReset();

        oxRegistry::unitTestReset();
        $oConfigFile = new oxConfigFile(self::$shopDirectory . "/config.inc.php");
        oxRegistry::set("oxConfigFile", $oConfigFile);
    }

    private function populateRequestVars() {
        foreach ($_GET as $k => $v) {
            $_REQUEST[$k] = $v;
        }
        foreach ($_POST as $k => $v) {
            $_REQUEST[$k] = $v;
        }
    }


    // ========================================================================

    // called from oxoutput overload
    public function registerOutput($name, $data) {
        $this->output .= $data;
    }

    // called from oxutils overload
    public function registerHeader($header) {
    }

    // called from oxutils overload
    public function registerRedirect($url, $code) {
        throw new Exception("Got unexpected redirect: $code $url");
        // var_dump(func_get_args());
    }

    // called from oxutils overload
    public function registerShowMessageAndExit($message) {
    }

    // called from oxutilsserver overload
    public function registerSetOxCookie($args) {
        list($name, $value) = $args;
        $this->cookies[$name] = $value;
        // debug('cookie: ' . $args[0] . ' = ' . $args[1]);
    }



}

