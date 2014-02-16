<?php

class oxRegistry extends oxRegistry_Original {

    // new
    public static function unitTestReset() {
        self::$_aInstances = array();
    }

}