<?php

class OxidTestDriverResponse {

    public $controller   = null;
    public $user         = null;
    public $titleTag     = null;
    public $html         = null;
    public $sessionId    = null;
    public $redirect     = null;
    public $basketItems  = null;
    public $time         = null;

    public $dom          = null;
    public $domXPath     = null;
    public $libXmlErrors = null;

    public function xpath($xpath) {
        return $this->getDomXPath()->query($xpath);
    }

    public function getDom() {
        if ($this->dom) return $this->dom;
        libxml_use_internal_errors(true);
        $this->dom = new DOMDocument();
        $this->dom->loadHTML($this->html);
        $this->libXmlErrors = libxml_get_errors();
        libxml_clear_errors();
        return $this->dom;
    }

    public function getDomXPath() {
        if ($this->domXPath) return $this->domXPath;
        $this->domXPath = new DOMXPath($this->getDom());
        return $this->domXPath;
    }

}

