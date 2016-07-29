<?php namespace Tinyf;
defined('APP_DEBUG') or define('APP_DEBUG',true);

if(APP_DEBUG) {
    error_reporting(-1); //报告所有 PHP 错误
}else{
    error_reporting(0); // 关闭所有PHP错误报告
}

define('BASE_DIR', dirname(dirname(__DIR__)));

$classLoader = include( __DIR__.'/../autoload.php' );

$__app = new App((new Container())->set('classLoader',$classLoader));

return $__app;