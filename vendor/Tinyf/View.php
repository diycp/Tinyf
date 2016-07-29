<?php namespace Tinyf;

/**
 * 视图装载类
 */
class View {
    protected $data = [];
    protected $viewName = '';
    public function getViewName() {
        return $this->viewName;
    }
    public function getData() {
        return $this->data;
    }
    public function make($viewName) {
        $this->viewName = $viewName;
        return $this;
    }
    public function with($key, $value='') {
        $this->data[$key] = $value;
        return $this;
    }
    public function __call($method, $params) {
        $this->with(lcfirst(substr($method,4)),$params[0]);
        return $this;
    }
}