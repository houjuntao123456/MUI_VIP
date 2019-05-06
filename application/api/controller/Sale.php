<?php

namespace app\api\controller;

use think\Db;
use app\api\service\sale\Sale as Sa;
// use app\api\service\Refunds as Rf;
use app\api\service\VipJoin;

class Sale extends Common
{
    public function sale()
    {
        $res = Sa::sale($this->data, $this->db);
        if ($res['code'] != 200) {
            $this->api(400, $res['msg']);
        } else {
            if ($this->isGetEndData == true) {
                $vip = new VipJoin($this->db, $this->data['order']['vip_code']);
                $this->api(200, 'sale success', $vip->select());
            } else {
                $this->api(200, 'sale success');
            }
        }
    }

    // 触发数据接口就可以了
    // public function refunds()
    // {
    //     $res = Rf::refunds($this->data, $this->db);
    //     if ($res['code'] != 200) {
    //         $this->api(400, $res['msg']);
    //     } else {
    //         if ($this->isGetEndData == true) {
    //             $vip = new VipJoin($this->db, $this->data['order']['vip_code']);
    //             $this->api(200, 'refunds success', $vip->select());
    //         } else {
    //             $this->api(200, 'refunds success');
    //         }
    //     }
    // }
}