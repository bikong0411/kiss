<?php
/**
 * Created by PhpStorm.
 * User: sky
 * Date: 14-10-1
 * Time: 10:54
 */

namespace kiss;


class Route {
    private static $self;
    private $_conf;
    private $_udi;

    private function __construct($conf = '') {
        $this->_conf = $conf;
        $this->_udi = array('module' => 'keys', 'controller' => 'keys', 'action' => 'keys');
    }

    public static function getInstance($conf = '') {
        if(!self::$self) {
            self::$self = new self($conf);
        }
        return self::$self;
    }
    public function setDefaultModule($module) {
        $this->_udi['module'] = $module;
    }

    public function udi($param = '') {
        if(!$param) {
            return $this->_udi;
        }
        $orign_udi = $this->_udi;
        if(is_array($param)) {
           foreach(array('module', 'controller', 'action') as $key) {
               if(isset($param[$key])) {
                   $this->_udi[$key] = $param[$key];
               }
           }
        } elseif (is_string($param)) {
            $param = str_replace("//", "/", $param);
            $ary = explode("/", $param);
            array_shift($ary);
            switch(count($ary)) {
                case 4:
                    list($this->_udi['module'], $this->_udi['controller'], $this->_udi['action']) = $ary;
                    break;
                case 3:
                    list($this->_udi['controller'], $this->_udi['action']) = $ary;
                    break;
                case 2:
                    $this->_udi['action'] = $ary;
            }
        }
        foreach($this->_udi as $key => $value) {
            if(!$value) {
                $this->_udi[$key] = $orign_udi[$key];
            }
        }
    }


    public function route() {
        if(isset($this->_udi['module'])) {
            if(in_array($this->_udi['module'], $this->_conf)) {
                return array(
                    'module' => $this->_udi['module'],
                    'controller' => isset($this->_conf[$this->_udi['module']][$this->_udi['controller']])
                                    ?$this->_conf[$this->_udi['module']][$this->_udi['controller']]:'default',
                    'action' => $this->_conf[$this->_udi['controller']]
                );
            }
        }

        $conf = isset( $this->_conf['default']) ? $this->_conf['default'] : $this->_conf;

        if(in_array($this->_udi['controller'], $conf)) {
            return array(
                'module' => $this->_udi['module'],
                'controller' => $this->_udi['controller'],
                'action' => $conf[$this->_udi['controller']]
            );
        }
        return $this->_udi;
    }
} 
