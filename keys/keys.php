<?php
/**
 * test controller
 * Created by PhpStorm.
 * User: sky
 * Date: 14-10-1
 * Time: 21:32
 */
namespace keys\keys;
use kiss\Controller;

class Keys extends Controller{
    public function keysAction() {
        echo "hello world", $this->_context->get("sky","Skkkky");
    }
}
