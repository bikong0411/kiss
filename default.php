<?php
/**
 * 入口文件
 * Created by PhpStorm.
 * User: sky
 * Date: 14-10-1
 * Time: 10:30
 */
require __DIR__."/App.php";

$app = new App();
$app->dispatch();