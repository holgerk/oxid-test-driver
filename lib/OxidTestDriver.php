<?php

require_once __DIR__ . '/OxidTestDriverResponse.php';


// needs to be defined before bootstrap, so we can return true for seo urls
function isSearchEngineUrl() {
    return OxidTestDriver::getCurrentInstance()->isSearchEngineUrl();
}


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
        oxUtilsObject::unitTestAddOverload('oxconfig');
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
    private $mocksByClass = array();
    private $redirect = null;
    private $startTime = null;
    private $overloadedConfigValues = array();

    public function __construct() {
        if (!self::$configured) {
            throw new Exception(
                "No patches applied!\n" .
                "Please call OxidTestDriver::configure(<shopDirectory>) first."
            );
        }
        self::$currentInstance = $this;
    }

    public function get($url, $params = array()) {
        return $this->request('GET', $url, $params);
    }

    public function post($url, $params = array()) {
        return $this->request('POST', $url, $params);
    }

    public function request($method, $url, $params = array()) {
        $this->reset();

        $method = strtoupper($method);

        if (is_string($params)) {
            parse_str($params, $params);
        }

        if (is_array($url)) {
            $params = $url;
            $url = '/index.php';
        }

        $urlParts = parse_url($url);

        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
            foreach ($queryParams as $key => $value) {
                $_GET[$key] = $value;
            }
        }

        foreach ($params as $key => $value) {
            $GLOBALS["_$method"][$key] = $value;
        }

        $isSeoUrl =
            isset($urlParts['path']) &&
            substr($urlParts['path'], 0, 10) != '/index.php' &&
            substr($urlParts['path'], 0, 2) != '/?' &&
            $urlParts['path'] != '/';
        if ($isSeoUrl) {
            $this->isSearchEngineUrl = true;
            $_SERVER['REQUEST_URI'] = $urlParts['path'];
            $_SERVER['SCRIPT_NAME'] = '/oxseo.php';
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

    /**
     * Resets shop state
     */
    public function reset() {
        $_GET = array();
        $_POST = array();
        $_SESSION = array();
        $_COOKIE = $this->cookies;
        $_REQUEST = array();
        $_SERVER = array();

        $this->output = '';
        // $this->cookies = array(); // no reset because they should persist
        // $this->mocksByClass = array(); // no reset because mocks should survive requests
        $this->redirect = null;
        $this->startTime = microtime(true);
        $this->isSearchEngineUrl = false;

        oxUtilsObject::getInstance()->unitTestReset();
        oxSuperCfg::unitTestReset();
        oxView::unitTestReset();

        oxRegistry::unitTestReset();
        $oConfigFile = new oxConfigFile(self::$shopDirectory . "/config.inc.php");
        oxRegistry::set("oxConfigFile", $oConfigFile);
    }

    /**
     * Returns a mockbuilder instance for a class created with oxnew
     *
     * This mock is than used as long this driver is the current one, to reset one needs to create
     * a new driver instance
     *
     * PHPUnit speficific
     */
    public function getMock($phpUnitTestCase, $class) {
        $class = strtolower($class);
        $oxidObjectFactory = oxUtilsObject::getInstance();

        $overloadedClass = $oxidObjectFactory->getClassName($class);
        $builder = $phpUnitTestCase->getMockBuilder($overloadedClass);
        $builder->disableOriginalConstructor();

        $mock = $builder->getMock();
        $this->mocksByClass[$class] = $mock;

        return $mock;
    }

    public function setConfigParam($name, $value) {
        $this->overloadedConfigValues[$name] = $value;
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

    private function populateRequestVars() {
        foreach ($_GET as $k => $v) {
            $_REQUEST[$k] = $v;
        }
        foreach ($_POST as $k => $v) {
            $_REQUEST[$k] = $v;
        }
    }


    // ========================================================================

    public function isSearchEngineUrl() {
        return $this->isSearchEngineUrl;
    }

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
    }

    // called from oxutilsobject overload
    public function findRegisteredMock($class) {
        $class = strtolower($class);
        if (isset($this->mocksByClass[$class])) {
            return $this->mocksByClass[$class];
        }
        return null;
    }

    // called from oxconfig overload
    public function hasOverloadedConfigParam($name) {
        return array_key_exists($name, $this->overloadedConfigValues);
    }

    // called from oxconfig overload
    public function getOverloadedConfigParam($name) {
        return $this->overloadedConfigValues[$name];
    }

}

