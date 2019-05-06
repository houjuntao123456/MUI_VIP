<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use lunar\Lunar;
use app\web\controller\Common;
use app\web\service\ErpWhere as EW;

/**
 * Author lhp
 * Date 2019/01/18
 * Description 系统首页
 */
class Index extends Common
{

    /**
     * 当日提醒
     * @param array $openCard          今日开卡数量
     * @param array $birth             今日生日人数
     * @param array $rlist             返单计划待审核
     * @param array $rinter            返单互动待审核
     * @param array $Salesvolume       消费额
     * @param array $Salesparts        消费件数 
     * @param array $Returnreminder    返单互动提醒  
     * @param array $Returnreminder356 365互动提醒
     * @param array $Returback         回头互动提醒
     */
    public function remind()
    {
        if (session('info.store') == '') {
            $stores = EW::getStore(EW::orgChild());
        } else {
            $stores = session('info.store');
        }

        $openCard = 0;//Db::table($this->db.'.vip_viplist')->where('reg_store', 'in', $stores)-> ('date_registration', 'today')->count();  //今日开卡 
        
        $lunar = new Lunar;
        $nong = $lunar->convertSolarToLunar(date('Y'), date('m'), date('d'));
        $vips = Db::table($this->db.'.vip_viplist')->where('store_code', 'in', $stores)->field('birthday,calendar')->select(); // 得到今天的农历
        if (!empty($vips)) {
            $birth = 0;
            foreach ($vips as $k=>$v) {
                switch ($v['calendar']) {
                    case 1:
                        if (date('md', $v['birthday']) == $nong[4].$nong[5]) {
                            $birth += 1;
                        }
                    break;
                    case 0:
                        if (date('md', $v['birthday']) == date('md')) {
                            $birth += 1;
                        }
                    break;
                }  
            }
        } else {
           $birth = 0; 
        }
        
        $rlist = Db::table($this->db.'.vip_reorder')->where('store_code', 'in', $stores)->where('status', 0)->count(); //返单计划待审核  
        $rinter = Db::table($this->db.'.vip_reorder_interaction')->where('store_code', 'in', $stores)->where('status', 0)->count(); //返单互动待审核  
        $Salesvolume = Db::table($this->db.'.vip_goods_order')->field('truncate(sum(real_pay), 2) as real_pay')->where('store_code', 'in', $stores)->whereTime('create_time', 'today')->find()['real_pay'];  // 销售额 
        if ($Salesvolume === null) {
            $Salesvolume = 0.00;
        }
        $Salesparts = Db::table($this->db.'.vip_goods_order')->field('sum(number) as num')->where('store_code', 'in', $stores)->whereTime('create_time', 'today')->find()['num'];  //消费件数  
        if ($Salesparts === null) {
            $Salesparts = 0;
        } 

        $staff_id = session('info.staff');

        $where[] = ['service_time', '>=', strtotime(date('Y-m-d'))];
        $where[] = ['service_time', '<=', strtotime(date('Y-m-d')) + 86400];
        $Returnreminder = Db::table($this->db.'.vip_reorder_interaction')
                        ->where('store_code', 'in', $stores)
                        ->where('executor_code', $staff_id)
                        ->where($where)
                        ->count(); //返单互动提醒  

        // $wheres[] = ['time', '>=', strtotime(date('Y-m-d'))];
        // $wheres[] = ['time', '<=', strtotime(date('Y-m-d')) + 86400];
        // $Returnreminder356 = Db::table($this->db.'.vip_365_interaction_foot')
        //                 ->where('executor_code', $staff_id)
        //                 ->where($wheres)
        //                 ->count(); //365互动提醒

        // $Returback = Db::table($this->db.'.vip_back_interaction_foot')
        //             ->alias('v')
        //             ->leftJoin($this->db.'.vip_viplist vip', 'vip.consultant_code = v.executor_code')
        //             ->field('v.time,vip.final_purchases')
        //             ->where('executor_code', $staff_id)
        //             ->select();
        // if ($Returback) {
        //     foreach ($Returback as $k=>$v) {
        //         if (time() <= $v['final_purchases'] + ($v['time'] * 86400) || time() >= $v['final_purchases'] + (($v['time'] + 1) * 86400)) {
        //             unset($Returback[$k]);
        //         }
        //     }
        //     $Returback = count($Returback);        
        // } else {
        //     $Returback = 0;
        // }

        webApi(0, '', 0, ['open' => $openCard, 'birt' => $birth, 'list' => $rlist, 'rinter' => $rinter, 'Salesvolume' => $Salesvolume, 'Salesparts' => $Salesparts, 'Returnreminder' => $Returnreminder]); //, 'Returnreminder356' => $Returnreminder356, 'Returback' => $Returback
    }

    /**
     * 图表一
     * @param array $d          消费额
     * @param array $frequency  消费次数
     * @param array $Number     消费件数
     * @param array $Joint_rate 连带率
     * @param array $days       这个月的天数
     */
    public function consumption()
    {
        $m = date('Y-m');
        $t = date('t');
        $d = []; 
        $frequency = []; 
        $Number = []; 
        $Joint_rate = [];
        
        $days = range(1, $t); 

        if (session('info.store') == '') {
            $stores = EW::getStore(EW::orgChild());
        } else {
            $stores = session('info.store');
        }

        for ($i = 1; $i <= $t; $i++) {
            
            $d[$i] = Db::table($this->db.'.vip_goods_order')->field('truncate(sum(real_pay), 2) as real_pay')->where('store_code', 'in', $stores)->whereBetweenTime('create_time', $m.'-'.$i)->find()['real_pay'] ?? 0;  // ->where($storeWhere)
            $frequency[$i] = Db::table($this->db.'.vip_goods_order')->field('vip_code')->where('store_code', 'in', $stores)->whereBetweenTime('create_time', $m.'-'.$i)->count();  //消费次数  ->where($storeWhere)
            $Number[$i] = Db::table($this->db.'.vip_goods_order')->field('sum(number) as num')->where('store_code', 'in', $stores)->whereBetweenTime('create_time', $m.'-'.$i)->find()['num'] ?? 0;  //消费件数  ->where($storeWhere)
           
            if ($frequency[$i] == 0 || $Number == 0) { //连带率
                $Joint_rate[$i] = 0;
            } else {
                $Joint_rate[$i] = number_format($Number[$i] / $frequency[$i], 2, '.', '');
            }
        }
        webApi(0, '', 0, ['days' => $days, 'd' => array_values($d), 'Joint_rate' => array_values($Joint_rate)]);
    }

    /**
     * @param array $k_price    客单价
     * @param array $j_price    件单价
    */
    public function Rechargeamount()  
    {
        $m = date('Y-m');
        $t = date('t');
        $k_price = [];
        $j_price = [];
        
        $days = range(1, $t);

        if (session('info.store') == '') {
            $stores = EW::getStore(EW::orgChild());
        } else {
            $stores = session('info.store');
        }

        for ($i = 1; $i <= $t; $i++) {

            $frequency[$i] = Db::table($this->db.'.vip_goods_order')->field('vip_code')->where('store_code', 'in', $stores)->whereBetweenTime('create_time', $m.'-'.$i)->count();  //消费次数  ->where($storeWhere)
            $Number[$i] = Db::table($this->db.'.vip_goods_order')->field('sum(number) as num')->where('store_code', 'in', $stores)->whereBetweenTime('create_time', $m.'-'.$i)->find()['num'] ?? 0;  //消费件数  ->where($storeWhere)
    
            $d[$i] = Db::table($this->db.'.vip_goods_order')->field('truncate(sum(real_pay), 2) as real_pay')->where('store_code', 'in', $stores)->whereBetweenTime('create_time', $m.'-'.$i)->find()['real_pay'] ?? 0;  // ->where($storeWhere)
            
            if ($frequency[$i] == 0 || $d == 0) { //客单价
                $k_price[$i] = 0;
            } else {
                $k_price[$i] = number_format($d[$i] / $frequency[$i], 2, '.', '');
            }

            if ($Number[$i] == 0 || $d == 0) { //件单价
                $j_price[$i] = 0;
            } else {
                $j_price[$i] = number_format($d[$i] / $Number[$i], 2, '.', '');
            
            }
        }
        unset($t);
        webApi(0, '', 0, ['days' => $days, 'k_price' => array_values($k_price), 'j_price' => array_values($j_price)]);
    }

}