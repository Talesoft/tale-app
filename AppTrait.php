<?php

namespace Tale;

trait AppTrait
{

    private $_app = null;

    public function setApp(App $app)
    {

        $this->_app = $app;
    }

    public function getApp()
    {

        return $this->_app;
    }
}