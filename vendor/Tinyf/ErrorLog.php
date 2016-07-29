<?php namespace Tinyf;

/**
 * 错误日志
 */
class ErrorLog {
    protected $debug;
    protected $path = '';
    protected $log  = [];
    protected $isPHP7;
    protected $errorLevel = array(
        E_ERROR              => 'Fatal Error',
        E_WARNING            => 'Warning',
        E_PARSE              => 'Parsing Error',
        E_NOTICE             => 'Notice',
        E_CORE_ERROR         => 'Core Error',
        E_CORE_WARNING       => 'Core Warning',
        E_COMPILE_ERROR      => 'Compile Error',
        E_COMPILE_WARNING    => 'Compile Warning',
        E_USER_ERROR         => 'User Error',
        E_USER_WARNING       => 'User Warning',
        E_USER_NOTICE        => 'User Notice',
        E_STRICT             => 'Runtime Notice',
        E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
    );
    /**
     * 架构函数
     * @param string $path  日志根目录
     * @param boolean $debug 是否打印日志 是=打印 否=写入文件
     */
    public function __construct($path,$debug) {
        $this->path   = rtrim($path).'/';
        $this->debug  = $debug;
        $this->isPHP7 = version_compare(PHP_VERSION,'7.0','>=');
        $this->register();
    }
    /**
     * 注册捕捉各种错误与异常的函数
     */
    protected function register() {
        set_exception_handler([$this,'exceptionHandler']);
        set_error_handler([$this,'errorHandler']);
        if(!$this->isPHP7) {
            register_shutdown_function([$this,'shutdownFunction']);
        }
    }
    /**
     * 格式化日志信息
     */
    protected function format($level,$message,$file,$line) {
        if($this->debug) {
            $log = "<br/><b>{$level}</b>:  {$message} in <b>{$file}</b> on line <b>{$line}</b><br/>";
        }else{
            $log = $level.':  '.$message.' in '.$file.' on line '.$line;
        }
        return $log;
    }
    /**
     * 异常处理
     */
    public function exceptionHandler($e) {
        if($e instanceof \ErrorException) {
            $level = $this->errorLevel[$e->getSeverity()];
        }else{
            $level = 'Exception';
        }
        $this->log[] = $this->format($level,$e->getMessage(),$e->getFile(),$e->getLine());
    }
    /**
     * 错误处理
     */
    public function errorHandler($level, $message, $file, $line) {
        throw new \ErrorException($message, 0, $level, $file, $line);
    }
    /**
     * 致命错误处理
     * php7及以上版本，此函数不必注册
     */
    public function shutdownFunction() {
        $error = error_get_last();
        if($error) {
            $this->log[] = $this->format($this->errorLevel[$error['type']],$error['message'],$error['file'],$error['line']);
        }
        $this->write();
    }
    /**
     * 打印或写入日志
     */
    public function write() {
        if($this->debug) {
            echo implode('<br>',$this->log);
        }elseif(count($this->log) > 0){
            $result = true;
            if(!is_dir($this->path)) {
                $result = false;
                $this->log[] = 'User Warning:  Error log root path does not exist '.$this->path;
            }else{
                $flag = chr(13).chr(10);
                $file = $this->path.date('Y-m-d').'.log';
                $result = file_put_contents($file,implode($flag,$this->log).$flag,FILE_APPEND);
                if($result === false) {
                    $this->log[] = "User Warning:  Write log failed {$file}";
                }else{
                    $result = true;
                }
            }
            if(!$result) {
                die(implode('<br>',$this->log));
            }
        }
        $this->log = [];
    }
    /**
     * 析构函数
     */
    public function __destruct() {
        if($this->isPHP7) {
            $this->write();
        }
    }
}