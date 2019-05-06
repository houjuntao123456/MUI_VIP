<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author hrl
 * Date 2018/1/21
 * Description STM员工管理 --- V票兑换记录
 */
class Vexchange extends Common
{
    /**
     * V票记录兑换表
     */
    public function index() {
        // 获取分页数据
        [$page, $limit] = [input('page'), input('limit')];
        // 搜索
        if (input('search') != '') {
            $where[] = ['name', 'like', '%'.input('search').'%'];
        } else {
            $where = true; // 永真条件 等于 没有条件
        }
        $data = Db::table($this->db.'.vip_exchange_record')->where($where)->order('create_time','desc')->select();
        //修改格式
        foreach ($data as $k => $v) {
            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        //统计数量
        $count = Db::table($this->db.'.vip_exchange_record')->count();
        webApi(0, '', $count, $data);
    }
}