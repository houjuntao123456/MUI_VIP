<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author hrl
 * Date 2018/1/17
 * Description STM员工管理 --- V票报表
 */
class Vtable extends Common
{
    /**
     * V票报表
     */
    public function index()
    {
        $adminOrg = session('info.admin_org');
        if ($adminOrg == '') {
            webApi(0, '', 0, []);
        }
        $rules = Db::table($this->db.'.vip_vticket_rule')->where('operate_code', session('info.staff'))->select();
        if (empty($rules)) {
            webApi(0, '', 0, []);
        }

        $codes = implode(',', array_column($rules, 'staff_codes'));
        // V票领取记录表  r.receive_claim 领取人  r.receive_number持有数量  e.price 兑换总金额  e.rate 兑换汇率   e.number 兑换数量
        $data = Db::table($this->db.'.vip_staff')
                ->alias('f')
                ->leftJoin($this->db.'.vip_vreceive r', 'r.receive_claim = f.code')
                ->leftJoin($this->db.'.vip_exchange_record e' ,'e.receive_claim = f.code')
                ->field('f.name, f.code , ifnull(r.receive_number, 0) as receive_number, ifnull(sum(e.price), 0) as price, ifnull(sum(e.number), 0) as exchange_number,ifnull(r.receive_number + ifnull(sum(e.number), 0), 0) as draw_number')
                ->where('f.code', 'in', $codes)
                ->group('f.code')
                ->select();
        
        webApi(0, '', count($data), $data);
    }
}