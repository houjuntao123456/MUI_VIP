<?php

namespace app\api\service;

use think\Db;

class VipJoin
{
    private $db;
    private $vipCode;

    private $vip = [
        'vip_base' => [],
        'vip_level' => [],
        'vip_join_store' => []
    ];

    public function __construct($db, $vipCode) {
        $this->db = $db;
        $this->vipCode = $vipCode;
    }

    public function select()
    {
        $this->base();
        $this->level();
        $this->joinStore();
        return $this->vip;
    }

    private function base()
    {
        $vip = Db::table($this->db.'.vip_viplist')->where('code', $this->vipCode)->find();
        unset($vip['id']);
        $this->vip['vip_base'] = $vip;
        return $this;
    }

    private function level()
    {
        $vipJoinLevel = Db::table($this->db.'.vip_vipjoin_level')->field('org_code,vip_code')->where('vip_code', $this->vipCode)->select();
        $this->vip['vip_level'] = array_combine( array_column($vipJoinLevel, 'org_code'), array_column($vipJoinLevel, 'vip_code') );
        return $this;
    }

    private function joinStore()
    {
        $this->vip['vip_join_store'] = Db::table($this->db.'.vip_vipjoin_store')
                                        ->field('store_code,total_value,residual_value,stored_value,total_frozen_value,total_integral,residual_integral,already_used_integral,offset_integral,gift_integral,consumption_times,consumption_number,total_consumption,first_time,final_purchases')
                                        ->where('vip_code', $this->vipCode)->select();
        return $this;
    }
}