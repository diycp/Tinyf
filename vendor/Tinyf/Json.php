<?php namespace Tinyf;

/**
 * Json响应
 */
class Json {
    public static function response(\Tinyf\View $view) {
        header("Content-type:text/html;charset=utf-8");
        header('Content-type: application/json');
        header('Content-type: text/json');
        echo json_encode($view->getData());
        return true;
    }
}