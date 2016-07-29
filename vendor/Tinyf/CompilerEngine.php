<?php namespace Tinyf;

/**
 * 编译引擎
 * 此处代码，借鉴了部分laravel的源码 :)
 */
class CompilerEngine {
    protected $path;
    protected $cachePath;
    protected $extension = '.html';
    protected $contentTags = ['{{', '}}'];
    protected $directive = [
        'Statements',
        'Comments',
        'RegularEchos',
    ];
    protected $customDirectives = [];
    public function __construct($path,$cachePath) {
        if(!is_dir($path)) {
            throw new \Exception("Templet root path does not exist {$path}");
        }
        if(!is_dir($cachePath)) {
            throw new \Exception("Templet cache root path does not exist {$cachePath}");
        }
        $this->path      = rtrim($path,'/').'/';
        $this->cachePath = rtrim($cachePath,'/').'/';
    }
    /**
     * 自定义模板扩展
     */
    public function addExtension($ext) {
        $this->extension = '.'.ltrim($ext,'.');
    }
    /**
     * 自定义模板定界符
     */
    public function addContentTags($left,$right) {
        $this->contentTags = [0=>$left,1=>$right];
    }
    /**
     * 获取编译后的模板文件地址
     */
    public function getCompiledFilePath($fileName) {
        return $this->cachePath.md5($fileName.$this->extension);
    }
    /**
     * 获取编译前的模板地址
     */
    public function getFilePath($fileName) {
        return $this->path.trim($fileName,'/').$this->extension;
    }
    /**
     * 自定义指令
     */
    public function addDirective($name, callable $handler) {
        $this->customDirectives[$name] = $handler;
    }
    /**
     * 编译
     */
    public function make($fileName) {
        $filePath = $this->getFilePath($fileName);
        if(!is_file($filePath)) {
            throw new \Exception("File does not exist {$filePath}");
        }
        $content = file_get_contents($filePath);
        if($content === false) {
            throw new \Exception("Fail to read file {$filePath}");
        }
        foreach($this->directive as $k=>$v ) {
            $content = $this->{"compile{$v}"}($content);
        }
        $compiledFilePath = $this->getCompiledFilePath($fileName);
        $result = file_put_contents($compiledFilePath,$content);
        if($result === false) {
            throw new \Exception("Save compiled template failed {$compiledFilePath}");
        }
    }
    /**
     * @指令编译
     */
    protected function compileStatements($input) {
        $callback = function($match) {
            $expression = isset($match[3]) ? $match[3] : $match;
            $method = 'compile'.ucfirst($match[1]);
            if(method_exists($this,$method)) {
                $match[0] = $this->$method($expression);
            }elseif(isset($this->customDirectives[$match[1]])) {
                $match[0] = call_user_func($this->customDirectives[$match[1]], $expression);
            }
            return isset($match[3]) ? $match[0] : $match[0].$match[2];
        };
        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $callback, $input);
    }
    /**
     * 注释语句编译
     */
    protected function compileComments($input) {
        $pattern = sprintf('/%s--((.|\s)*?)--%s/', $this->contentTags[0], $this->contentTags[1]);
        return preg_replace($pattern, '<?php /*$1*/ ?>', $input);
    }
    /**
     * 规律的echo语句编译
     */
    protected function compileRegularEchos($input) {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);
        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];
            $wrapped = $this->compileEchoDefaults($matches[2]);
            return $matches[1] ? substr($matches[0], 1) : '<?php echo '.$wrapped.'; ?>'.$whitespace;
        };
        return preg_replace_callback($pattern, $callback, $input);
    }
    public function compileEchoDefaults($value) {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
    }
    /**
     * include语句
     */
    protected function compileInclude($expression) {
        $pattern = '~["|\']{1,1}(.*?)["|\']{1,1}~';
        $replacement = "'$1'";
        return '<?php include ($CompilerMain->make'.preg_replace($pattern, $replacement, $expression).'); ?>';
    }
    /**
     * if语句
     */
    protected function compileIf($expression) {
        return "<?php if{$expression}: ?>";
    }
    protected function compileElse($expression) {
        return '<?php else: ?>';
    }
    protected function compileElseif($expression) {
        return "<?php elseif{$expression}: ?>";
    }
    protected function compileEndif($expression) {
        return '<?php endif; ?>';
    }
    /**
     * for语句
     */
    protected function compileFor($expression) {
        return "<?php for{$expression}: ?>";
    }
    protected function compileEndfor($expression){
        return '<?php endfor; ?>';
    }
    /**
     * foreach语句
     */
    protected function compileForeach($expression) {
        return "<?php foreach{$expression}: ?>";
    }
    protected function compileEndforeach($expression) {
        return '<?php endforeach; ?>';
    }
    /**
     * while语句
     */
    protected function compileWhile($expression) {
        return "<?php while{$expression}: ?>";
    }
    protected function compileEndwhile($expression){
        return '<?php endwhile; ?>';
    }
}