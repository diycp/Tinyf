<?php
if(!function_exists('app')) {
    /**
     * 返回$app对象
     */
    function app() {
        global $__app;
        return $__app;
    }
}

if(!function_exists('container')) {
    /**
     * 返回$container对象
     */
    function container() {
        global $__app;
        return $__app->container();
    }
}

if(!function_exists('view')) {
    /**
     * 获取并返回视图对象
     */
    function view() {
        global $__app;
        return $__app->container()->get('view');
    }
}

if(!function_exists('url')) {
    /**
     * 根据路由生成 url
     */
    function url($routeUri,array $params) {
        return container()->get('route')->url($routeUri,$params);
    }
}