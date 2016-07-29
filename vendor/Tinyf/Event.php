<?php namespace Tinyf;

/**
 * 事件
 */
class Event {
    protected static $events = []; //事件池
    /**
     * 返回事件 或 事池
     */
    public static function get($name=null) {
        if(is_null($name)) {
            return self::$events;
        }
        return isset(self::$events[$name]) ? self::$events[$name] : false;
    }

    /**
     * 判断事件是否存在
     * @param  string $name 事件名称
     * @param  string|array|callback $handler 全局函数|对象与方法数组|类与静态方法数组|匿名函数
     * @return boolean 有=true 无=false
     */
    public static function has($name,$handler=null) {
        if(!isset(self::$events[$name]) || empty(self::$events[$name])) {
            return false;
        }

        if(is_null($handler)) {
            return  true;
        }

        $flag = false;
        foreach(self::$events[$name] as $k => $event) {
            if($event[0] === $handler) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    /**
     * 绑定事件
     * @param string $name 事件名称
     * @param string|array|callback $handler 全局函数|对象与方法数组|类与静态方法数组|匿名函数
     * @param mixed $data 传递给 $handler 的数据
     * @param boolean $append 是否最后执行
     */
    public static function on($name,$handler,$data=null,$append=true) {
        if($append || !isset(self::$events[$name])) {
            self::$events[$name][] = [$handler,$data];
        }else{
            array_unshift(self::$events[$name],[$handler,$data]);
        }
    }

    /**
     * 取消事件
     * @param  string $name 事件名称
     * @param  string|array|callback $handler 全局函数|对象与方法数组|类与静态方法数组|匿名函数
     * @return boolean 成功=true 失败=false
     */
    public static function off($name,$handler=null) {
        if(!isset(self::$events[$name])) {
            return false;
        }

        if(is_null($handler)) {
            unset(self::$events[$name]);
            return  true;
        }

        $flag = false;
        foreach(self::$events[$name] as $k => $event) {
            if($event[0] === $handler) {
                unset(self::$events[$name][$k]);
                $flag = true;
            }
        }
        return $flag;
    }

    /**
     * 触发事件
     * @param  string $name 事件名称
     * @param  mixed $data 传递给 $handler 的数据
     */
    public static function trigger($name,$data=null) {
        if(isset(self::$events[$name])) {
            foreach(self::$events[$name] as $k => $event) {
                if(is_null($event[1])) {
                    if(is_null($data)) {
                        $stop = call_user_func($event[0]);
                    }else{
                        $stop = call_user_func($event[0],$data);
                    }
                }else{
                    if(is_null($data)) {
                        $stop = call_user_func($event[0],$event[1]);
                    }else{
                        $stop = call_user_func($event[0],$event[1],$data);
                    }
                }
                if($stop) { //如果 回调函数返回真值 则停止执行
                    break;
                }
            }
        }
    }
}