<?php

require_once __DIR__ . '/OxidTestDriverResponse.php';


class OxidTestDriver {

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

        if (!file_exists(self::$shopDirectory . '/bootstrap.php')) {
            throw new Exception(
                "No bootstrap.php found!\n" .
                "Please check shopDirectory: $shopDirectory!"
            );
        }
        if (class_exists('oxutilsobject', false)) {
            throw new Exception(
                "Oxid's bootstrap.php allready loaded!\n" .
                "Please call OxidTestDriver::configure(<shopDirectory>) first."
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
            'oxview'        => array(
                'search'  => 'class oxView',
                'replace' => 'class oxView_Original'),
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
    private $redirect = null;
    private $startTime = null;

    public function __construct() {
        if (!self::$configured) {
            throw new Exception(
                "No patches applied!\n" .
                "Please call OxidTestDriver::configure(<shopDirectory>) first."
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
        $this->reset();

        $method = strtoupper($method);

        if (is_string($params)) {
            parse_str($params, $result);
            $GLOBALS["_$method"] = $result;
        } else if (is_array($params)) {
            $GLOBALS["_$method"] = $params;
        } else {
            throw new Exception("Wrong request param type: " . gettype($params));
        }
        $this->populateRequestVars();

        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de,en-US;q=0.8,en;q=0.6';

        Oxid::run();
        $response = $this->createResponse();

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
        $response = new OxidTestDriverResponse;
        $response->controller  = oxRegistry::getConfig()->getActiveView();
        $response->user        = $response->controller->getUser();
        $response->titleTag    = $this->getTitleTag();
        $response->html        = $this->output;
        $response->sessionId   = (isset($this->cookies['sid'])) ? $this->cookies['sid'] : null;
        $response->redirect    = $this->redirect;
        $response->basketItems = oxRegistry::getSession()->getBasket()->getContents();
        $response->time        = microtime(true) - $this->startTime;

        return $response;
    }

    private function reset() {
        $_GET = array();
        $_POST = array();
        $_SESSION = array();
        $_COOKIE = $this->cookies;
        $_REQUEST = array();

        $this->output = '';
        $this->cookies = array();
        $this->redirect = null;
        $this->startTime = microtime(true);

        oxUtilsObject::getInstance()->unitTestReset();
        oxSuperCfg::unitTestReset();
        oxView::unitTestReset();

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
        $this->redirect = new StdClass;
        $this->redirect->url = $url;
        $this->redirect->code = $code;
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

