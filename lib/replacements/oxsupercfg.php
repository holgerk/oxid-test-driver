<?php

class oxSuperCfg extends oxSuperCfg_Original {

    public static function unitTestReset() {
        self::$_oConfig = null;
        self::$_oSession = null;
        self::$_oRights = null;
        self::$_oActUser = null;
        self::$_blIsAdmin = null;
    }
}
