<?php

class OxidTestDriverOxConfig extends OxidTestDriverOxConfig_parent {

    // overload
    public function getConfigParam($name) {
        $driver = OxidTestDriver::getCurrentInstance();
        if ($driver->hasOverloadedConfigParam($name)) {
            return $driver->getOverloadedConfigParam($name);
        }
        return parent::getConfigParam($name);
    }

}
