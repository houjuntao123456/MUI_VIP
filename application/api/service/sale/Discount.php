<?php

namespace app\api\service\sale;

use think\Db;
use lunar\Lunar;

class Discount
{
    private $db;
    private $vip;
    private $order;
    private $orderInfo;
    
    public function __construct($db, $order, $orderInfo) {
        [$this->db, $this->order, $this->orderInfo] = [
            $db,       $order,        $orderInfo
        ];
        $this->setDefault();
        $this->vip = Db::table($this->db.'.vip_viplist')->field('level_code,birthday,calendar,extension')->where('code', $this->order['vip_code'])->find();
    }

    public function dis()
    {
        $this->levelDis();
        $this->activityDis();
        $this->affectDis();
        $this->birthdayDis();
        $this->optimal();
        $this->total();
        return [$this->order, $this->orderInfo];
    }

    /**
     * 级别折扣计算
     */
    private function levelDis()
    {
        $sql = 'select * from '.$this->db.'.vip_viplevel where code = (
                    select level_code from '.$this->db.'.vip_vipjoin_level where vip_code = "'.$this->order['vip_code'].'" and org_code = (
                        select org_code from '.$this->db.'.vip_store where code = "'.$this->order['store_code'].'"
                ))';
        $level = Db::query($sql);
        if ($level) {
            foreach ($this->orderInfo as $k=>$v) {
                $this->orderInfo[$k]['level_dis'] = $level[0]['discount'];
            }
        }
        return $this;
    }

    /**
     * 活动折扣计算
     */
    private function activityDis()
    {
        if (empty($this->vip['level_code'])) {
            $where[] = ['store_code', '=', $this->order['store_code']];
            $where[] = ['level_code', '=', $this->vip['level_code']];
            $where[] = ['time_start', '<=', time()];
            $where[] = ['time_end', '>=', time()];
            
            $canTake = Db::table($this->db.'.vip_vipactivity')->where($where)->order('exclusive_discounts', 'asc')->select();
            if (!empty($canTake)) {
                $g = new Goods;
                foreach ($canTake as $k=>$v) {
                    if ($v['type'] == 0) {
                        $canTake[$k]['project'] = explode(',', $g->classfiyChild($v['project'], $this->db)->getGoods());
                    } else {
                        $canTake[$k]['project'] = explode(',', $v['project']);
                    }
                }

                $disGoods = [];
                foreach ($canTake as $k=>$v) {
                    for ($i = 0; $i < count($v['project']); $i++) {
                        if ( !isset($disGoods[$v['project'][$i]]) || $disGoods[$v['project'][$i]] > $v['exclusive_discounts'] ) {
                            $disGoods[$v['project'][$i]] = $v['exclusive_discounts'];
                        }
                    }
                }
                
                foreach ($this->orderInfo as $k=>$v) {
                    if (isset($disGoods[$v['goods_code']])) {
                        $this->orderInfo[$k]['activity_dis'] = $disGoods[$v['goods_code']];
                    }
                }
            }
        }
        return $this;
    }

    /**
     * 感动特权计算
     */
    private function affectDis()
    {
        if ( !empty($this->vip['extension']) && empty($this->vip['level_code']) ) {
            $ext = json_decode($this->vip['extension'], true);

            $where[] = ['store_code', '=', $this->order['store_code']];
            $where[] = ['level_code', '=', $this->vip['level_code']];
            $canTake = Db::table($this->db.'.vip_vipdiscount')->where($where)->select();
            
            if (!empty($canTake)) {
                foreach ($canTake as $k=>$v) {
                    if (array_key_exists($v['username'], $ext) && is_string($ext[$v['username']])) {
                        // 如果他大于最大时间 或者小于最小时间 说明不在活动期限内 干掉
                        if ( strtotime(date('Y-').date('m-d', strtotime($ext[$v['username']]))) > (strtotime(date('Y-m-d')) + (($v['limited_time_end'] + 1) * 24 * 60 *60)) || 
                            strtotime(date('Y-').date('m-d', strtotime($ext[$v['username']]))) < (strtotime(date('Y-m-d')) - ($v['limited_time_start'] * 24 * 60 *60)) 
                        ) {
                            unset($canTake[$k]);
                        }
                    } else {
                        unset($canTake[$k]);
                    }
                }
                
                if (!empty($canTake)) {
                    foreach ($canTake as $k=>$v) {
                        if ($v['type'] == 0) {
                            $canTake[$k]['project'] = explode(',', $g->classfiyChild($v['project'], $this->db)->getGoods());
                        } else {
                            $canTake[$k]['project'] = explode(',', $v['project']);
                        }
                    }

                    $disGoods = [];
                    foreach ($canTake as $k=>$v) {
                        for ($i = 0; $i < count($v['project']); $i++) {
                            if ( !isset($disGoods[$v['project'][$i]]) || $disGoods[$v['project'][$i]] > $v['exclusive_discounts'] ) {
                                $disGoods[$v['project'][$i]] = $v['exclusive_discounts'];
                            }
                        }
                    }
                    
                    foreach ($this->orderInfo as $k=>$v) {
                        if (isset($disGoods[$v['goods_code']])) {
                            $this->orderInfo[$k]['affect_dis'] = $disGoods[$v['goods_code']];
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * 生日折扣计算
     */
    private function birthdayDis()
    {
        if (!empty($this->vip['birthday']) && !empty($this->vip['level_code'])) {
            // 计算生日时间戳
            if ($this->vip['calendar'] == 0) {
                $lunar = new Lunar;
                $time = $lunar->convertLunarToSolar('2018', date('m', $this->vip['birthday']), date('d', $this->vip['birthday']));
                $birthdayTime = strtotime($time[0].'-'.$time[1].'-'.$time[2]);
            } else {
                $birthdayTime = strtotime(date('Y-').date('m-d', $this->vip['birthday']));
            }

            $where[] = ['store_code', '=', $this->order['store_code']];
            $where[] = ['level_code', '=', $this->vip['level_code']];
            $where[] = ['prev_days', '>=', ($birthdayTime - strtotime(date('Y-m-d'))) / 86400];
            $where[] = ['after_days', '>=', (strtotime(date('Y-m-d')) - $birthdayTime) / 86400];
            $canTake = Db::table($this->db.'.vip_vipbirthday')->where($where)->select();
            
            if (!empty($canTake)) {
                foreach ($canTake as $k=>$v) {
                    if ($v['type'] == 0) {
                        $canTake[$k]['project'] = explode(',', $g->classfiyChild($v['project'], $this->db)->getGoods());
                    } else {
                        $canTake[$k]['project'] = explode(',', $v['project']);
                    }
                }

                $disGoods = [];
                foreach ($canTake as $k=>$v) {
                    for ($i = 0; $i < count($v['project']); $i++) {
                        if ( !isset($disGoods[$v['project'][$i]]) || $disGoods[$v['project'][$i]] > $v['discount'] ) {
                            $disGoods[$v['project'][$i]] = $v['discount'];
                        }
                    }
                }
                
                foreach ($this->orderInfo as $k=>$v) {
                    if (isset($disGoods[$v['goods_code']])) {
                        $this->orderInfo[$k]['birthday_dis'] = $disGoods[$v['goods_code']];
                    }
                }
            }
        }
        return $this;
    }

    /**
     * 设置默认值
     */
    private function setDefault()
    {
        foreach ($this->orderInfo as $k=>$v) {
            $this->orderInfo[$k]['level_dis'] = 1;
            $this->orderInfo[$k]['activity_dis'] = 1;
            $this->orderInfo[$k]['affect_dis'] = 1;
            $this->orderInfo[$k]['birthday_dis'] = 1;
        }
        return $this;
    }

    /**
     * 选择最优惠折扣
     */
    private function optimal()
    {
        foreach ($this->orderInfo as $k=>$v) {
            $optimal = min($v['level_dis'], $v['activity_dis'], $v['affect_dis'], $v['birthday_dis']);
            $disPrice = round($optimal * $v['price'], 2);
            $this->orderInfo[$k]['discount'] = $optimal;
            $this->orderInfo[$k]['dis_price'] = $disPrice;
            $this->orderInfo[$k]['dis_money'] = $v['number'] * $disPrice;
            $this->orderInfo[$k]['money'] = $v['number'] * $v['price'];
            unset($this->orderInfo[$k]['level_dis'], $this->orderInfo[$k]['activity_dis'], $this->orderInfo[$k]['affect_dis'], $this->orderInfo[$k]['birthday_dis']);
        }
        return $this;
    }

    /**
     * 计算总金额
     */
    private function total()
    {
        $this->order['money'] = array_sum( array_column($this->orderInfo, 'price') );
        $this->order['dis_money'] = array_sum( array_column($this->orderInfo, 'dis_price') );
        $this->order['number'] = array_sum( array_column($this->orderInfo, 'number') );
        return $this;
    }
}