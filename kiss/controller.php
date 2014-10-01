<?php
/**
 * Created by PhpStorm.
 * User: sky
 * Date: 14-10-1
 * Time: 23:23
 */

namespace kiss;

use kiss\Context;

class Controller {
    protected $_context;
    public function __construct() {
        $this->_context = Context::getInstance();
    }
} 