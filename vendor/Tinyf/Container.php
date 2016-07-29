<?php namespace Tinyf;

/**
 * 依赖注入容器
 */
class Container {
    protected $instance           = []; //实例
    protected $constructParameter = []; //构造函数形参
    protected $reflection         = []; //类反射
    protected $aliasORclosure     = []; //别名或闭包
    protected $param              = []; //实参
    /**
     * 容器构造函数
     */
    public function __construct() {
        $this->set(__CLASS__,$this);
    }
    /**
     * 写入依赖
	 * @return object 容器本身
     */
    public function set() {
        $param = func_get_args();
        $num   = func_num_args();
        unset($this->instance[$param[0]]);
        if($num == 2) {
            if(is_object($param[1]) && !is_callable($param[1])) {
                $this->instance[$param[0]] = $param[1];
            }else{
                if(is_array($param[1])) {
                    $this->param[$param[0]] = $param[1]; //类名与参数
                }else{
                    $this->aliasORclosure[$param[0]] = $param[1]; //接口与类名 | 别名与类名 | 类名与闭包 | 别名与闭包
                }
            }
        }elseif($num == 3) {
            if($param[1] instanceof Closure) { //类名与闭包与参数
                $this->aliasORclosure[$param[0]] = $param[1];
                $this->param[$param[0]]          = $param[2];
            }
        }
        return $this;
    }
    /**
     * 获取对象
     * @param  string $class 类名
	 * @param  array  $param  闭包的参数或者是类的参数
	 * @return object 实例化的对象
     */
    public function get($class,$param=[]) {

        if(isset($this->instance[$class])) return $this->instance[$class];
        if(empty($param) && isset($this->param[$class])) $param = $this->param[$class];
        if(!is_array($param)) {
            $param = [$param];
        }
        if(isset($this->aliasORclosure[$class])) {
            $aliasORclosure = $this->aliasORclosure[$class];
            if(is_callable($aliasORclosure)) {
                array_unshift($param,$this);
                $this->instance[$class] = call_user_func_array($aliasORclosure,$param); //闭包
            }else{
                $this->instance[$class] = $this->get($aliasORclosure,$param); //接口对应的类
            }
        }else{
            $this->instance[$class] = $this->make($class,$param);
        }
        return $this->instance[$class];
    }
    /**
     * 创建一个实例
     * @param  string $class 类名
     * @param  array  $param 实例化类时所需的参数 形参名就是key值
     * @return object 实例化的对象
     */
    protected function make($class,$param=[]) {
        $reflection = $this->getReflection($class);
        if($reflection->isInstantiable() === false) {
            throw new \Exception("Can't instantiate class {$class}");
        }else{
            $depend = $this->getConstructParameter($class,$param);
            return $reflection->newInstanceArgs($depend);
        }
    }
    /**
     * 获取构造函数参数
     * @param  string $class 类名
     * @param  array  $param 实例化类时所需的参数 形参名就是key值
     * @return array  构造函数实际所需参数
     */
    protected function getConstructParameter($class,$param=[]) {
        if(!is_array($param)) {
            $param = [$param];
        }
        if(!isset($this->constructParameter[$class])) {
            $depend = [];
            $constructor = $this->getReflection($class)->getConstructor();
            if(!is_null($constructor)) {
                foreach ($constructor->getParameters() as $value) {
                    if(isset($param[$value->name])) {
                        $depend[] = $param[$value->name]; //没有默认值的形参
                    }elseif($value->isDefaultValueAvailable()) {
                        $depend[] = $value->getDefaultValue(); //有默认值得形参
                    }else{
                        $tmp = $value->getClass();
                        if(is_null($tmp)) {
                            throw new \Exception("Class parameters can not be getClass {$class}");
                        }
                        $depend[] = $this->get($tmp->getName()); //依赖的类或接口
                    }
                }
            }
            $this->constructParameter[$class] = $depend;
        }
        return $this->constructParameter[$class];
    }
    /**
     * 获取反射类
     * @param  string $class 类名
     * @return object 返回类的反射实例
     */
    protected function getReflection($class) {
        if(!isset($this->reflection[$class])) $this->reflection[$class] = new \ReflectionClass($class);
        return $this->reflection[$class];
    }
    /**
     * 获取一个方法的依赖
     * @param  string $class  类名
     * @param  string $method 方法名
     * @param  array  $param 调用方法时所需参数 形参名就是key值
     * @return array  返回方法调用所需依赖
     */
    public function getMethodParameter($class,$method,$param=[]) {
        if(!is_array($param)) {
            $param = [$param];
        }
        $ReflectionMethod = new \ReflectionMethod($class,$method);
        $depend = array();
        foreach($ReflectionMethod->getParameters() as $value) {
            if(isset($param[$value->name])) {
                $depend[] = $param[$value->name];
            }elseif($value->isDefaultValueAvailable()){
                $depend[] = $value->getDefaultValue();
            }else{
                $tmp = $value->getClass();
                if(is_null($tmp)) {
                    throw new \Exception("Class parameters can not be getClass {$class}");
                }
                $depend[] = $this->get($tmp->getName());
            }
        }
        return $depend;
    }
    /**
     * 获取一个函数的依赖
     * @param  string|callable $func
     * @param  array  $param 调用方法时所需参数 形参名就是key值
     * @return array  返回方法调用所需依赖
     */
    public function getFucntionParameter($func,$param=[]) {
        if(!is_array($param)) {
            $param = [$param];
        }
        $ReflectionFunc = new \ReflectionFunction($func);
        $depend = array();
        foreach($ReflectionFunc->getParameters() as $value) {
            if(isset($param[$value->name])) {
                $depend[] = $param[$value->name];
            }elseif($value->isDefaultValueAvailable()){
                $depend[] = $value->getDefaultValue();
            }else{
                $tmp = $value->getClass();
                if(is_null($tmp)) {
                    throw new \Exception("Function parameters can not be getClass {$func} {$class}");
                }
                $depend[] = $this->get($tmp->getName());
            }
        }
        return $depend;
    }
}