<?php

class TestDriverOxUtilsServer extends TestDriverOxUtilsServer_parent {

    // overload
    public function setOxCookie($sName, $sValue = "", $iExpire = 0, $sPath = '/', $sDomain = null, $blToSession = true, $blSecure = false) {
        call_user_func(
            array(TestDriver::getCurrentInstance(), 'registerSetOxCookie'),
            func_get_args());
    }

}