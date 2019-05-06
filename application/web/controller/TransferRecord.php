<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author lhp
 * Date 2019/01/01
 * Description  会员转移记录
 */
class TransferRecord extends Common
{
    
    public function index()
    {
        // 分页信息
        [$page, $limit, $lookup] = [input('page'), input('limit'), input('lookup')];

        //统计数量
        $count = Db::table($this->db. '.vip_transfer_record')->count();

        //模糊查询
        if ($lookup) {
            $where[] = ['username|operate_name', 'like', '%'.$lookup.'%'];
        } else {
            $where = true;
        }
        unset($lookup);
        //查询
        $data = Db::table($this->db.'.vip_transfer_record')
                ->field('id, vip_code, username, pre_transfer_name, post_transfer_name, pre_transfer_store_name, post_transfer_store_name, transfer_time, operate_name')
                ->where($where)
                ->page($page, $limit)
                ->select();
                unset($page, $limit, $where);
        foreach ($data as $k=>$v) {
            $data[$k]['transfer_time'] = date('Y-m-d H:i:s', $v['transfer_time']);
        }
        webApi(0, '', $count, $data);
    }
}