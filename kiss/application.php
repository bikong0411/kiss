<?php
/**
 * Created by PhpStorm.
 * User: sky
 * Date: 14-10-1
 * Time: 10:46
 */

namespace kiss;

use kiss\Route;
use kiss\Context;

class Application {

    protected  $_route;
    protected  $_context;
    private  $_autoLoad_namespace = array('kiss' => __DIR__);
    protected $_route_rule = array();
    private  $_document_root = __DIR__;

    public function  __construct() {
        $this->autoLoad();
        $this->init();
    }

    protected function init() {
        $this->_context =  Context::getInstance();
        $this->_route = Route::getInstance($this->_route_rule);
    }

    public function setAutoLoadNameSpaces(array $params) {
        foreach($params as $basedir => $namespace) {
            $this->_autoLoad_namespace[$namespace] = $basedir;
        }
    }

    public function getAutoLoadNameSpaces() {
        return array_keys($this->_autoLoad_namespace);
    }

    public function addAutoLoadNameSpace($namespace, $basedir) {
        $this->_autoLoad_namespace[$namespace] = $basedir;
    }

    public function autoLoad() {
        spl_autoload_register(array($this, '_autoLoad'));
    }

    public function dispatch() {
        if(isset($_GET['controller'])) {
            $this->_route->udi($_GET);
        }else{
            $request_uri = $_SERVER['REQUEST_URI'];
            $url_info = parse_url($request_uri);
            $this->_route->udi($url_info['path']);
        }
        $udi = $this->_route->route();
        $access_control = $this->_access_control();
        if(!$access_control) {
            echo $this->_deny();
            return;
        }

        $file = $this->_document_root."/".$udi['module']."/".$udi['controller'].".php";
        if(is_file($file)) {
            $class = "\\credit\\{$udi['module']}\\".ucfirst($udi['controller']);
            $cls = new $class();
            $method = "{$udi['action']}Action";
            if(method_exists($cls, $method)) {
                return $cls->$method();
            }
        }
        echo $this->_method_not_exists();
        exit;
    }

    public function forward($udi) {
        $this->_route->udi($udi);
        $udi = $this->_route->route();
        $access_control = $this->_access_control();
        if(!$access_control) {
            echo $this->_deny();
            return;
        }

        $file = $this->_document_root."/".$udi['module']."/".$udi['controller'].".php";
        if(is_file($file)) {
            $class = "\\credit\\{$udi['module']}\\".ucfirst($udi['controller']);
            $cls = new $class();
            $method = "{$udi['action']}Action";
            if(method_exists($cls, $method)) {
                return $cls->$method();
            }
        }
        return $this->_method_not_exists();
    }

    public function redirect($url) {
        header("HTTP/1.1 301 Move Peramanent");
        header("Location: $url");
        exit;
    }

    protected function _access_control() {
        return true;
    }

    protected function _deny() {
        return $this->_context->encode_json(array('status' => 403, 'error' => 'Forbidden!'));
    }

    protected function _method_not_exists() {
        return $this->_context->encode_json(array('status' => 403, 'error' => 'method is not exists!'));
    }

    public function setDocumentRoot($dir) {
        $this->_document_root = $dir;
    }

    public function _autoLoad($class_name) {
        if(!class_exists($class_name, false) && !interface_exists($class_name, false)) {
            $_p = explode("\\", $class_name );
            $namespace = array_shift($_p);
            if(isset($this->_autoLoad_namespace[$namespace])) {
                $_p = array_map(function($str) {return strtolower($str);}, $_p);
                $file = $this->_autoLoad_namespace[$namespace]. DIRECTORY_SEPARATOR .implode(DIRECTORY_SEPARATOR , $_p).".php";
                if(is_file($file)) {
                    return  require $file;
                }
            }
        }
    }
} 