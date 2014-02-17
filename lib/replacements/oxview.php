<?php

class oxView extends oxView_Original {

    public static function unitTestReset() {
        self::$_blExecuted = false;
    }

}
