<?php namespace Tinyf;

/**
 * 模板响应
 */
class Template {
    public static function response(\Tinyf\View $view) {
        header("Content-type:text/html;charset=utf-8");
        $compilerMain = container()->get('compilerMain');
        $compiledFilePath = $compilerMain->make($view->getViewName());
        echo $compilerMain->render($compiledFilePath,$view->getData());
        return true;
    }
}