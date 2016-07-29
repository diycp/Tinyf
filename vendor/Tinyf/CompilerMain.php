<?php namespace Tinyf;

/**
 * 编译入口类
 */
class CompilerMain {
    protected $CompilerEngine;
    public function __construct(\Tinyf\CompilerEngine $CompilerEngine) {
        $this->CompilerEngine = $CompilerEngine;
    }
    /**
     * 编译模板
     */
    public function make($fileName) {
        $fileName = str_replace('.','/',$fileName);
        $filePath         = $this->CompilerEngine->getFilePath($fileName);
        $compiledFilePath = $this->CompilerEngine->getCompiledFilePath($fileName);
        if(!is_file($filePath)) {
            throw new \Exception("File does not exist {$filePath}");
        }
        if(!file_exists($compiledFilePath) || filemtime($compiledFilePath) < filemtime($filePath)) {
            $this->CompilerEngine->make($fileName);
		}
        return $compiledFilePath;
    }
    /**
     * 渲染数据
     */
    public function render($compiledFilePath,$__data=[]) {
        $__data = (array) $__data;
        $__data['CompilerMain'] = $this;
        ob_start();
        extract($__data);
        $result = include $compiledFilePath;
        if($result === false) {
            throw new \Exception("Load file failed {$compiledFilePath}");
        }
        unset($__data,$result);
        return ltrim(ob_get_clean());
    }
}