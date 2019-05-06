<?php

namespace app\api\service;

use think\Db;

class LevelUp
{
    private $db;
    private $org;
    private $vip;
    private $val;
    private $stores;
    private $levelData;

    private $before_level; // 晋升前等级
    private $after_level;  // 晋升后等级
    private $up_cause;     // 为何晋升

    private $saleAndRefundsIsAllList = false; // 启用整单方案还是详情方案

    public function __construct($db, $val, $store, $vip, $allList = false) {
        $this->db = $db;
        $this->vip = $vip;
        $this->val = $val;
        $this->saleAndRefundsIsAllList = (bool)$allList;
        $this->setDefault($store);
    }

    /**
     * 升级
     */
    public function up()
    {
        $this->callFunc();
        if ($this->after_level['uid'] > $this->before_level['uid']) {
            return ['code' => 1, 'level' => $this->after_level['code'], 'org' => $this->org];
        } else {
            return ['code' => 0];
        }
    }

    /**
     * 降级
     */
    public function down()
    {
        $this->callFunc();
        if ($this->after_level['uid'] < $this->before_level['uid']) {
            return ['code' => 1, 'level' => $this->after_level['code'], 'org' => $this->org];
        } else {
            return ['code' => 0];
        }
    }

    private function callFunc()
    {
        // $this->cumtrapz();
        // $this->introduce();
        $this->consume();
        if ($this->val > 0) {
            $this->firstConsume();
            $this->oneceConsume();
        }
        // $this->oneceRecharge(); 充值 废弃
    }

    /**
     * 累积积分晋升
     */
    private function cumtrapz()
    {
        return $this;
    }

    /**
     * 累积转介绍人数晋升
     */
    private function introduce()
    {
        return $this;
    }

    /**
     * 累积消费金额晋升
     */
    private function consume()
    {
        if (!empty($this->levelData)) {
            $val = $this->val;
            $p = $this->levelData;
            $db = $this->db;

            $os = new OrgStore;
            $where[] = ['vip_code', '=', $this->vip];
            $where[] = ['store_code', 'in', $os->orgs($this->org, $this->db)->stores()];
            $where[] = ['status', '=', 1];
            
            $z = ['uid' => -1, 'code' => ''];
            foreach ($p as $k=>$v) {
                if ($this->saleAndRefundsIsAllList === false) {
                    $money = Db::table($db.'.vip_goods_order_info')->field('sum(dis_price * real_number) as money')->where($where)->where('create_time', '>=', time() - ($v['total_amount_time'] * 86400))->find();
                } else {
                    $money = Db::table($db.'.vip_goods_order')->field('sum(dis_money) as money')->where($where)->where('create_time', '>=', time() - ($v['total_amount_time'] * 86400))->find();
                }
                if (isset($money['money']) && intval($money['money']) + $val > $v['total_amount'] && $v['uid'] > $z['uid']) {
                    $z = ['uid' => $v['uid'], 'code' => $v['code']];
                }
            }
            if ( $z['uid'] > $this->after_level['uid'] ) {
                $this->after_level = $z;
                $this->up_cause = '累积消费金额晋升';
            }
        }
        return $this;
    }

    /**
     * 首次消费金额晋升
     */
    private function firstConsume()
    {
        if (!empty($this->levelData)) {
            $val = $this->val;
            $p = $this->levelData;
            $z = ['uid' => -1, 'code' => ''];
            foreach ($p as $k=>$v) {
                if ($val >= $v['first_amount'] && $v['uid'] > $z['uid']) {
                    $z = ['uid' => $v['uid'], 'code' => $v['code']];
                }
            }
            if ( $z['uid'] > $this->after_level['uid'] ) {
                $this->after_level = $z;
                $this->up_cause = '首次消费金额晋升';
            }
        }
        return $this;
    }

    /**
     * 单次消费金额晋升
     */
    private function oneceConsume()
    {
        if (!empty($this->levelData)) {
            $val = $this->val;
            $p = $this->levelData;
            $z = ['uid' => -1, 'code' => ''];
            foreach ($p as $k=>$v) {
                if ($val >= $v['single_amount'] && $v['uid'] > $z['uid']) {
                    $z = ['uid' => $v['uid'], 'code' => $v['code']];
                }
            }
            if ( $z['uid'] > $this->after_level['uid'] ) {
                $this->after_level = $z;
                $this->up_cause = '单次消费金额晋升';
            }
        }
        return $this;
    }

    /**
     * 单次充值金额晋升
     */
    private function oneceRecharge()
    {
        if (!empty($this->levelData)) {
            $val = $this->val;
            $p = $this->levelData;
            $z = ['uid' => -1, 'code' => ''];
            foreach ($p as $k=>$v) {
                if ($val >= $v['single_recharge'] && $v['uid'] > $z['uid']) {
                    $z = ['uid' => $v['uid'], 'code' => $v['code']];
                }
            }
            if ( $z['uid'] > $this->after_level['uid'] ) {
                $this->after_level = $z;
                $this->up_cause = '单次充值金额晋升';
            }
        }
        return $this;
    }

    private function setDefault($store)
    {
        $storeData = Db::table($this->db.'.vip_store')->field('org_code')->where('code', $store)->find();
        $this->org = $storeData['org_code'];

        $this->levelData = Db::table($this->db.'.vip_vippromote')
                            ->alias('p')
                            ->leftJoin($this->db.'.vip_viplevel l', 'l.code = p.levelname')
                            ->field('p.introduction,p.introduction_time,p.total_amount,p.total_amount_time,p.total_integral,p.total_integral_time,p.first_amount,p.single_amount,p.single_recharge,l.uid,l.code')
                            ->where('p.org_code', $storeData['org_code'])
                            ->where('p.state', 1)
                            ->select();

        $where[] = ['vip_code', '=', $this->vip];
        $where[] = ['org_code', '=', $storeData['org_code']];
        $levelJoin = Db::table($this->db.'.vip_vipjoin_level')->field('level_code')->where($where)->find();
        if (empty($levelJoin)) {
            $this->before_level = ['uid' => -1, 'code' => ''];
            $this->after_level = ['uid' => -1, 'code' => ''];
        } else {
            $level = Db::table($this->db.'.vip_viplevel')->field('uid,code')->where('code', $levelJoin['level_code'])->find();
            $this->before_level = $level;
            $this->after_level = ['uid' => -1, 'code' => ''];
        }
        return $this;
    }
}