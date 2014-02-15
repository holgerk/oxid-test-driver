<?php

require_once getShopBasePath()."core/smarty/Smarty.class.php";

class TestDriverOxUtils extends TestDriverOxUtils_parent {

    // overload
    public function setHeader($header) {
        TestDriver::getCurrentInstance()->registerHeader($header);
    }

    // overload
    protected function _simpleRedirect($url, $code) {
        TestDriver::getCurrentInstance()->registerRedirect($url, $code);
    }

    // overload
    public function showMessageAndExit($message) {
        TestDriver::getCurrentInstance()->registerShowMessageAndExit($message);
    }


}
