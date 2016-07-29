<?php namespace App\Controllers\Home;
use Tinyf\Event;
use App\Models\Test;

/**
 * 欢迎控制器
 */
class Welcome {

    /**
     * 控制器执行前绑定或取消事件
     */
    public function event() {
        //Event::on('afterRequest',['Tinyf\Json','response']);
    }

    public function index($id=10) {
        $test = new Test();
        $userName = $test->get($id);
        return view()->make('Home.Welcome.index')->withUserName($userName);
    }
}