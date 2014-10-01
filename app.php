<?php
/**
 * Created by PhpStorm.
 * User: sky
 * Date: 14-10-1
 * Time: 22:30
 */
require __DIR__."/kiss/application.php";

class App extends \kiss\Application {

    public function __construct() {
        define('DEBUG', isset($_GET['debug']));
        ini_set("date.timezone", 'Asia/ShangHai');
        if(DEBUG) {
            ini_set('display_errors', true);
            ini_set('error_reporting', E_ALL ^ E_NOTICE);
        }
        $this->addAutoLoadNameSpace("credit", __DIR__);
        $this->autoLoad();
        $this->_route_rule = require __DIR__."/conf/route.conf.php";
        $this->setDocumentRoot(__DIR__);
        $this->init();
    }

    protected function _access_control() {
        return true;
    }
} 
