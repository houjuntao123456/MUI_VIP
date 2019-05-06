<?php

namespace app\web\controller;

use think\Db;

class WechatMenu extends Common
{
    public function top()
    {
        $db = $this->db;
        $data = Db::table($db.'.vip_wechat_menu')->where('pid', 0)->select();
        $count = Db::table($db.'.vip_wechat_menu')->where('pid', 0)->count();
        webApi(0, '', $count, $data);
    }
}