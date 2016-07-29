<?php namespace Tinyf;

/**
 * 路由类
 */
class Route {
    protected static $instance;  //当前路由类的实例
    protected $routeUri    = [];    //路由规则
    protected $routeData   = [];   //路由数据
    protected $routeMethod = []; //路由的请求类型
    protected $routeWhere  = [];  //路由参数值过滤规则
    protected $routeName   = [];  //路由别名
    protected $methodWhite = [   //请求类型白名单
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'HEAD',
        'OPTIONS'
    ];

    public function __construct() {
        self::$instance = $this;
    }

    public static function __callstatic($method, $params) {
        if(is_null(self::$instance)) {
            throw new \Exception(__CLASS__.' instance does not exist');
        }

        self::$instance->routeUri[]     = trim($params[0],'/');
        self::$instance->routeData[] = $params[1];
        self::$instance->routeMethod[]  = strtoupper($method);

        return self::$instance;
    }

    /**
     * 路由的约束正则
     */
    public function where($routeName,$pattern='([^/]+)') {
        if(!is_array($routeName)) {
            $routeName = ["{$routeName}"=>$pattern];
        }
        $pos = count($this->routeUri) - 1;
        if(!isset($this->routeWhere[$pos])) $this->routeWhere[$pos] = [];
        $this->routeWhere[$pos] = array_merge($this->routeWhere[$pos],$routeName);
        return $this;
    }

    /**
     * 设置路由名称
     */
    public function name($routeName) {
        $this->routeName[$routeName] = count($this->routeUri) - 1;
    }

    /**
     * 根据名称获取路由规则
     */
    public function getRouteUriByName($routeName) {
        return isset($this->routeName[$routeName]) ? $this->routeUri[$this->routeName[$routeName]] : false;
    }

    /**
     * 根据路由规则或路由规则别名生成路由
     * @param  string $routeUri 路由规则或其别名
     * @param  array  $params   参数
     * @return string|boolean 成功返回 字符串 失败返回 false
     */
    public function url($routeUri,array $params) {
        $tmp = $this->getRouteUriByName($routeUri);
        if($tmp) {
            $routeUri = $tmp;
        }

        $routeUri = trim($routeUri,'/');
        if(stripos($routeUri,'{') !== false) {
            $flag = true;
            $routeUri = preg_replace_callback('~{(.*?)}~',function($matches) use (&$params,&$flag) {
                $trimNode = rtrim($matches[1],'?'); //干净的节点
                if(substr($matches[1],-1) == '?') {
                    if(isset($params[$trimNode])) {
                        $tmp = $params[$trimNode];
                        unset($params[$trimNode]);
                        return $tmp;
                    }
                }else{
                    if(!isset($params[$trimNode])) {
                        $flag = false;
                    }else{
                        $tmp = $params[$trimNode];
                        unset($params[$trimNode]);
                        return $tmp;
                    }
                }
                //print_r($matches);
            },$routeUri);
            if(!$flag) return false;
        }

        $routeUri = '/'.$routeUri;

        if(count($params)) {
            $routeUri = rtrim($routeUri,'/').'/?'.http_build_query($params);
        }

        if(stripos($routeUri,'//') !== false) {
            return false;
        }

        return $routeUri;
    }

    /**
     * 匹配调度
     * @param  string $requestUri    请求的uri
     * @param  string $requestMethod 请求的类型
     * @return
     */
    public function dispatch($requestUri, $requestMethod) {
        $requestUri = trim($requestUri,'/');
        $requestMethod = strtoupper($requestMethod);

        if(!in_array($requestMethod,$this->methodWhite)) {
            return false;
        }

        $requestUriArr = $this->uriToArray($requestUri);
        $requestUriCounter = count($requestUriArr);
        foreach($this->routeUri as $pos=>$routeUri) {
            if($this->routeMethod[$pos] != $requestMethod && $this->routeMethod[$pos] != 'ANY') {
                continue; //请求类型不匹配
            }

            if(stripos($routeUri,'{') === false) { //是静态路由规则
                if($routeUri == $requestUri) {
                    $result = [
                        'data'=>$this->routeData[$pos],
                        'params'=>array()
                    ];
                    return $result;
                }
                continue;  //静态路由匹配失败 跳过进行下一条匹配
            }

            //开始匹配动态路由
            $routeUriArr = $this->uriToArray($routeUri);
            if($requestUriCounter > count($routeUriArr)) {
                continue; //请求节点数大于路由规则的节点数
            }

            preg_match_all('~{(.*?)}~', $routeUri, $routeMatches); //挑选出所有的参数节点

            $params = array();
            $flag = true;
            foreach($routeUriArr as $key=>$node) {
                if(in_array($node,$routeMatches[0])) { //是个参数节点
                    $trimNode = trim(substr($node,1,-1),'?'); //干净的节点
                    if(substr($node,-2) == '?}') { //可选参数
                        if(isset($requestUriArr[$key])) { //存在参数值
                            if(isset($this->routeWhere[$pos][$trimNode])) { //存在参数值过滤规则
                                if(preg_match("~{$this->routeWhere[$pos][$trimNode]}~",$requestUriArr[$key])) { //过滤成功
                                    $params[substr($node,1,-2)] = $requestUriArr[$key];
                                }else{
                                    $flag = false;
                                    break; //过滤失败
                                }
                            }elseif(strlen($requestUriArr[$key]) > 0){ //参数值不是空字符才可获取
                                $params[substr($node,1,-2)] = $requestUriArr[$key];
                            }
                        }
                    }else{ //必须参数
                        if(isset($requestUriArr[$key])) {
                            $where = isset($this->routeWhere[$pos][$trimNode]) ? "~{$this->routeWhere[$pos][$trimNode]}~" : '~[^\s]+~';
                            if(preg_match($where,$requestUriArr[$key])) { //过滤成功
                                $params[substr($node,1,-1)] = $requestUriArr[$key];
                            }else{
                                $flag = false;
                                break; //过滤失败
                            }
                        }else{
                            $flag = false;
                            break; //参数值不存在
                        }
                    }
                }else{ //是个普通节点
                    if(!isset($requestUriArr[$key]) || $requestUriArr[$key] != $node) {
                        $flag = false;
                        break;
                    }
                }
            }
            if($flag) {
                $result = [
                    'data'=>$this->routeData[$pos],
                    'params'=>$params
                ];
                return $result;
            }
        }

        return false;
    }

    /**
     * 将uri切割成数组
     */
    protected function uriToArray($uri) {
        return preg_split('|(?mi-Us)/+|',$uri);
    }
}