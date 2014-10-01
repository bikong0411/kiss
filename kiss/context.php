<?php
/**
 * Created by PhpStorm.
 * User: sky
 * Date: 14-10-1
 * Time: 10:54
 */

namespace kiss;


class Context {
    private static  $self;
    private function  __construct() {

    }

    public static function getInstance() {
        if(!self::$self) {
            self::$self = new self;
        }
        return self::$self;
    }

    public function get($key, $default = '')  {
        return isset($_GET[$key])? $_GET[$key] : $default;
    }

    public function post($key, $default = '') {
        return isset($_POST[$key])? $_POST[$key] : $default;
    }

    public function input($key, $default = '') {
        $input = file_get_contents("php://stdin");
        return isset($input[$key]) ? $input[$key] : $default;
    }

    public function isGet() {
        return $_SERVER['REQUEST_METHOD'] === "GET";
    }

    public function isPost() {
        return $_SERVER['REQUEST_METHOD'] === "POST";
    }

    public function isPut() {
        return $_SERVER['REQUEST_METHOD'] === "PUT";
    }

    public function isDelete() {
        return $_SERVER['REQUEST_METHOD'] === "DELETE";
    }

    public function isAjax() {
        return $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest";
    }

    public function ip() {
        foreach(array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR') as $key) {
            if(isset($_SERVER[$key]) && $_SERVER[$key]) {
                return $_SERVER[$key];
            }
        }
        return "0.0.0.0";
    }

    public function header($key) {
        return isset($_SERVER["HTTP_$key"])? $_SERVER["HTTP_$key"] : null;
    }

    public function encode_json($array) {
        return json_encode($array);
    }

    public function decode_json($string) {
        return json_decode($string, true);
    }
} 