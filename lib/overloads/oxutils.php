<?php

require_once getShopBasePath()."core/smarty/Smarty.class.php";

class OxidTestDriverOxUtils extends OxidTestDriverOxUtils_parent {

    // overload
    public function setHeader($header) {
        OxidTestDriver::getCurrentInstance()->registerHeader($header);
    }

    // overload
    protected function _simpleRedirect($url, $code) {
        OxidTestDriver::getCurrentInstance()->registerRedirect($url, $code);
    }

    // overload
    public function showMessageAndExit($message) {
        OxidTestDriver::getCurrentInstance()->registerShowMessageAndExit($message);
    }


}
