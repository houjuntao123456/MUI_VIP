<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/07
 * Description 提成记录
 */
class Commission extends Common
{
    /**
     * 提成记录表
     */
    public function commissionSel()
    {
        //分页信息
        [$page, $limit] = [input('page'), input('limit')];
        
        //查询
        if (input('splb') != '') {
            $where[] = ['v.org_code', '=', input('splb')];
        }
        if (input('store_code') != '') {
            $where[] = ['v.store_code', '=', input('store_code')];
        }
        if (input('staff_code') != '') {
            $where[] = ['v.staff_code', '=', input('staff_code')];
        }
        if (input('commission') != '') {
            $where[] = ['v.source', '=', input('commission')];
        }
        if (input('start') != '') {
            $where[] = ['v.create_time', '>=', strtotime(input('start'))];
        }
        if (input('end') != '') {
            $where[] = ['v.create_time', '<=', strtotime(input('end'))];
        }
        if (!isset($where)) {
            $where = true;
        }
        //当前登入人
        $operate = Db::table($this->db.'.vip_staff')
                ->where('code', session('info.staff'))
                ->find();

        //门店限制
        if ($operate['id'] == 1) {
            $store = true;
        } else if ($operate['store_code'] == "") {
            $store = true;
        } else {
            $store[] = ['v.store_code', '=', $operate['store_code']];
        }
        //统计数量
        $count = Db::table($this->db.'.vip_push_money')
                ->alias('v')
                ->leftJoin($this->db. '.vip_org vg', 'vg.code = v.org_code')//所属机构
                ->leftJoin($this->db. '.vip_store vs', 'vs.code = v.store_code')//门店
                ->field('v.*, vg.name vgname, vs.name vsname')
                ->where($where)
                ->where($store)
                ->count();
        // 查询数据
        $data = Db::table($this->db.'.vip_push_money')
                ->alias('v')
                ->leftJoin($this->db. '.vip_org vg', 'vg.code = v.org_code')//所属机构
                ->leftJoin($this->db. '.vip_store vs', 'vs.code = v.store_code')//门店
                ->field('v.*,vg.name vgname, vs.name vsname')
                ->where($where)
                ->where($store)
                ->order('v.create_time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();
        
        //更改格式
        foreach ($data as $k => $v) {
            if ($v['create_time'] !== 0) {
                $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }
        unset($page, $where, $limit, $store);
        
        webApi(0, '', $count, $data);
    }
}