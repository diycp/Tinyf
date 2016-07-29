<?php namespace Tinyf;

/**
 * 应用主体
 */
class App {
    protected $container; //容器
    const EVENT_BEFORE_REQUEST = 'beforeRequest'; //请求之前执行
    const EVENT_AFTER_REQUEST = 'afterRequest'; //请求之后执行
    protected $controllerName = ''; //当前请求的控制器名
    protected $methodName = ''; //当前请求的方法名

    /**
     * 架构函数
     */
    public function __construct(\Tinyf\Container $container) {
        $this->container = $container; //持有容器
        $this->container->set('errorLog',new ErrorLog(BASE_DIR.'/storage/error',APP_DEBUG)); //实例化日志捕捉类
        $this->container->set('config',$this->instanceConfig()); //实例化配置加载类
        $this->container->set('route',$this->instanceRoute()); //实例化路由类
        $this->container->set('compilerMain','Tinyf\CompilerMain'); //注册模板引擎入口别名
        $this->container->set('compilerEngine','Tinyf\CompilerEngine'); //注册模板引擎别名
        $this->container->set('Tinyf\CompilerEngine',['path'=>BASE_DIR.'/app/Views','cachePath'=>BASE_DIR.'/storage/view']); //注册模板引擎参数
        $this->container->set('view','Tinyf\View'); //注册视图装载类
    }

    /**
     * 实例化缓存类
     */
    protected function instanceConfig() {
        $configCacheFile = BASE_DIR.'/storage/cache/config.php';
        $config = new Config();
        if(is_file($configCacheFile) && !APP_DEBUG) { //没开debug才能走缓存
            $config->set(include($configCacheFile));
        }
        return $config;
    }

    /**
     * 缓存配置文件
     */
    protected function cacheConfig() {
        $configCacheFile = BASE_DIR.'/storage/cache/config.php';
        if(!APP_DEBUG) { //没开debug
            if(!is_file($configCacheFile)) { //缓存不存在 则生成缓存
                $config = $this->container->get('config')->get();
                file_put_contents($configCacheFile,'<?php return '.var_export($config,true).';');
            }
        }elseif(is_file($configCacheFile)) {
            unlink($configCacheFile); //缓存存在则删除缓存
        }
    }

    /**
     * 实例化路由类
     */
    protected function instanceRoute() {
        $config = $this->container->get('config');
        $routeFile = $config['app']['route']['path'];
        if($config['app']['route']['cache']) {
            $routeCacheFile = BASE_DIR.'/storage/cache/route.php';
            if(is_file($routeCacheFile) && filemtime($routeCacheFile) > filemtime($routeFile)) {
                $route = include($routeCacheFile);
            }else{
                $route = new Route();
                include($routeFile);
                $routeStr = '<?php return unserialize(base64_decode(\''.base64_encode(serialize($route)).'\'));';
                file_put_contents($routeCacheFile,$routeStr);
            }
        }else{
            $route = new Route();
            $result = include($routeFile);
        }
        return $route;
    }

    /**
     * 返回容器实例
     */
    public function container() {
        return $this->container;
    }

    /**
     * 返回当前控制器名
     */
    public function controllerName() {
        return $this->controllerName;
    }

    /**
     * 返回当前方法名
     */
    public function methodName() {
        return $this->methodName;
    }

    /**
     * 调度整个框架
     */
    public function fighting() {
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        $result = $this->container->get('route')->dispatch($uri,$method);
        if($result == false) {
            header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
            throw new \Exception('routing failure uri: '.$uri);
        }

        if(is_callable($result['data'])) {
            $depend = $this->container->getFucntionParameter($result['data'],$result['params']);
            call_user_func_array($result['data'],$depend);
        }else{
            $parts = explode('/',$result['data']);
            list($controllerName , $methodName) = explode('@',end($parts));

            $this->controllerName = $controllerName;
            $this->methodName = $methodName;

            $controllerName = "App\Controllers\\{$controllerName}";

            $controller = $this->container->get($controllerName);
            $depend = $this->container->getMethodParameter($controllerName,$methodName,$result['params']);

            if(method_exists($controller,'event')) {
                call_user_func([$controller,'event']);
            }

            Event::on('afterRequest',['Tinyf\Template','response']);

            Event::trigger(self::EVENT_BEFORE_REQUEST,$controller);
            $view = call_user_func_array([$controller,$methodName],$depend);
            if($view instanceof \Tinyf\View ) {
                Event::trigger(self::EVENT_AFTER_REQUEST,$view);
            }
        }
    }

    public function __destruct() {
        $this->cacheConfig();
    }
}