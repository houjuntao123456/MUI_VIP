<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use auth\Auth;

class Common extends Controller
{
    protected $db;

    public function initialize()
    {
        if (input('access_token') == '' || empty(session('info'))) {
            webApi(1001);
        }
        
        $info = Db::table(session('info.db').'.vip_staff')->where('access_token', input('access_token'))->find();
        if (empty($info) || $info['ua'] != $_SERVER['HTTP_USER_AGENT']) {
            webApi(1001);
        }
        $this->db = session('info.db');
        $str = request()->controller().'/'.request()->action();
        $skipAuth = config('skip_auth.router');
        if (!in_array($str, $skipAuth)) {
            $auth = new Auth(session('info.db'));
            // $res = $auth->check($str, session('info.staff'));
            // if (!$res) {
            //     webApi(1, '没有权限');
            // }
        }
    }

    /**
     * 记录日志
     * @param string $token 用户token
     * @param string $module 操作模块
     * @param string $mode 操作方式
     * @param string $oi 操作数据
     */
    protected function insertLogs($token, $module, $mode, $oi)
    {
        $data = [
            'operate_code' => session('info.staff'),
            'module' => $module,
            'mode' => $mode,
            'oi' => $oi,
            'ip' => request()->ip(),
            'create_time' => time()
        ];
        Db::table($this->db.'.vip_logs')->insert($data);
    }
}