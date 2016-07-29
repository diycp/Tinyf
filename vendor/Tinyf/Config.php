<?php namespace Tinyf;

/**
 * 配置载入类
 */
class Config implements \ArrayAccess {
	protected $config = array();
	public function offsetExists($key) {
		return isset($this->config[$key]);
	}
	public function offsetGet($key) {
		if(!isset($this->config[$key])) $this->load($key);
		return $this->config[$key];
	}
	public function offsetSet($key,$value) {
		$this->config[$key] = $value;
	}
	public function offsetUnset($key) {
		unset($this->config[$key]);
	}
	public function get() {
		return $this->config;
	}
    public function set(array $array) {
        $this->config = array_merge($this->config,$array);
    }
	protected function load($key) {
        $file = BASE_DIR.'/config/'.$key.'.php';
        if(!is_file($file) || !($this->config[$key] = include($file))) {
            throw new \Exception("Can't include config file {$file}");
        }
    }
}