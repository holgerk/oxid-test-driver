<?php

class OxidTestDriverOxUtilsServer extends OxidTestDriverOxUtilsServer_parent {

    // overload
    public function setOxCookie($sName, $sValue = "", $iExpire = 0, $sPath = '/', $sDomain = null, $blToSession = true, $blSecure = false) {
        call_user_func(
            array(OxidTestDriver::getCurrentInstance(), 'registerSetOxCookie'),
            func_get_args());
    }

}