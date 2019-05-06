<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/14
 * Description A件单价
 */
class Loypiece extends Common
{
    /**
     * A件单价查表
     */
    public function index()
    {
        //获取分页数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search') ];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($this->db.'.vip_rfm_a')
                    ->where('store_code', $search)
                    ->count();
            //查询
            $data = Db::table($this->db.'.vip_rfm_a')
                    ->where('store_code', $search)
                    ->order('a_create_time','desc') //按照登记时间降序排列
                    ->page($page, $limit)
                    ->select();
            //周期
            $a_consumption = Db::table($this->db.'.vip_rfm_days')->field('a_consumption')->where('store_code', $search)->find();
            // 查询人数需要条件
            $vips = Db::table($this->db.'.view_viplist')
                    ->where('store_code', $search)
                    ->where('rfm_days', '<=', $a_consumption['a_consumption'])
                    ->where('rfm_days', '>=', 0)
                    ->field('code')
                    ->select();

            if ($vips) {
                $cards = array_column($vips, 'code');
            } else {
                $cards = [];
            }
            //查询出金额
            $fc_money = Db::table($this->db.'.vip_goods_order')
                        ->field('sum(real_pay) as pay')
                        ->where('vip_code','in', $cards)
                        ->where('status', 0)
                        ->group('vip_code')
                        ->select();
            //查询出件数
            $fc_number = Db::table($this->db.'.vip_goods_order')
                        ->field('sum(number) as num')
                        ->where('vip_code', 'in', $cards)
                        ->where('status', 0)
                        ->group('vip_code')
                        ->select();

            if ($fc_money && $fc_number) {
                $fct = array_column($fc_money, 'pay');
                $fctt = array_column($fc_number, 'num');
                $n = count($fc_money);
            } else {
                $fct = [];
                $fctt = [];
                $n = 0;
            }

            foreach($data as $k=>$v){
                //时间格式的转换
                $data[$k]['a_create_time_g'] = date('Y-m-d H:i:s', $v['a_create_time']);
                if ($data[$k]['a_update_time'] == "") {
                    $data[$k]['a_update_time_g'] = $data[$k]['a_create_time_g'];
                } else {
                    $data[$k]['a_update_time_g'] = date('Y-m-d H:i:s', $v['a_update_time']);
                }
                //金额格式
                $data[$k]['a_intervalone_g'] = number_format($v['a_intervalone'], 2);
                $data[$k]['a_intervaltwo_g'] = number_format($v['a_intervaltwo'], 2);
                //拼成在表中的显示
                $data[$k]['Index_interval'] =$data[$k]['a_intervalone_g'].' ≤ A < '.$data[$k]['a_intervaltwo_g'];
                //周期赋值
                $data[$k]['a_consumption'] =  $a_consumption['a_consumption'];
                //会员人数
                if ($fct && $fctt) {
                    $c = 0;
                    for ($i = 0; $i < $n; $i++) {
                        //客单价 = 金额/件数
                        if ($fct[$i] == 0 || $fctt[$i] == 0) {
                            $am = 0;
                        } else {
                            $am = $fct[$i]/$fctt[$i];
                        }
                        if ($am >= $v['a_intervalone'] && $am < $v['a_intervaltwo']) {
                            $c++;
                        }
                    }
                    $data[$k]['numbertime'] = $c;
                } else {
                    $data[$k]['numbertime'] = 0;
                }
            }
            
            unset($page, $limit, $search, $vips, $a_consumption, $cards, $c, $fctt, $fct, $n, $fc_number, $fc_money);

        } else {
            $count = 0;
            $data = [];
        }

        webApi(0, '', $count, $data);
    }

    /**
     * 修改A类型
     */
    public function pieceEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $data = [
            'a_intervalone' => input('a_intervalone'),
            'a_intervaltwo' => input('a_intervaltwo'),
            'a_update_time' => time(),
            'id' => $id
        ];
        //判断
        if($data['a_intervalone'] >= $data['a_intervaltwo']){
            webApi(0,'error',0,'区间(金额)不符合规则，前面不能大于等于后面！');
        }
        $this->Sectionjudge($data, $id, input('store_code'));
        $res = Db::table($this->db.'.vip_rfm_a')->update($data);
        unset($id,$data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 周期
     */
    public function pieceDayEdit()
    {
        $data = [
            'a_consumption' => input('a_consumption')
        ]; 
        $res = Db::table($this->db.'.vip_rfm_days')->where('store_code', input('store_code'))->update($data);
        unset($data);
        if ($res) {
            webApi(0, 'yes', 0, '成功!');
        } else {
            webApi(0, 'no', 0, '失败!');
        }
    }

    /**
     * 限制修改区间
     */
    protected function Sectionjudge($up, $id = 0, $store)
    {
        if ($id !== 0) {
            $where[] = ['id', '<>', $id];
        } else {
            $where = true;
        }
        $meats = Db::table($this->db.'.vip_rfm_a')->where($where)->where('store_code', $store)->select();
        foreach ($meats as $v) {
            if (  ($up['a_intervalone'] >= $v['a_intervalone'] && $up['a_intervalone'] < $v['a_intervaltwo']) || ($up['a_intervaltwo'] > $v['a_intervalone'] && $up['a_intervaltwo'] <= $v['a_intervaltwo']) ) {
                webApi(0, 'no', 0, '该区间规则已存在！');
            }
        }
    }

    /**
     * 查人数
     */
    public function lookPeople()
    {
        //分页
        [$page, $limit, $id, $store] = [input('page'), input('limit'), input('id'), input('store_code')];
        
        if ($id == null) {
            webApi(1, '参数错误！');
        }
        //限制
        $rday = Db::table($this->db.'.vip_rfm_a')->where('id', $id)->find();
        // 周期
        $a_consumption = Db::table($this->db.'.vip_rfm_days')->field('a_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('piece,v.*')
                ->where('v.store_code', $store)
                ->where('f.dates', '>=', time() - (86400 * $a_consumption['a_consumption']))
                ->where('f.vip_code','<>',0)
                ->where('f.piece', '>=', $rday['a_intervalone'])
                ->where('f.piece', '<', $rday['a_intervaltwo'])
                ->page($page, $limit)
                ->select();

        $count = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('piece,v.*')
                ->where('v.store_code', $store)
                ->where('f.dates', '>=', time() - (86400 * $a_consumption['a_consumption']))
                ->where('f.vip_code','<>',0)
                ->where('f.piece', '>=', $rday['a_intervalone'])
                ->where('f.piece', '<', $rday['a_intervaltwo'])
                ->count();

        unset($id, $page, $store, $limit, $rday, $a_consumption);        
        
        webApi(0, '', $count, $vipData);
    }

    //短信
    public function smsSend()
    {
        //获取所需数据
        [$id, $store] = [input('id'), input('store_code')];
        //判断
        if ($id == null) {
            webApi(1, '参数错误！');
        }
        //限制
        $rday = Db::table($this->db.'.vip_rfm_a')->where('id', $id)->find();
        // 周期
        $a_consumption = Db::table($this->db.'.vip_rfm_days')->field('a_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('piece,v.*')
                ->where('v.store_code', $store)
                ->where('f.dates', '>=', time() - (86400 * $a_consumption['a_consumption']))
                ->where('f.vip_code','<>',0)
                ->where('f.piece', '>=', $rday['a_intervalone'])
                ->where('f.piece', '<', $rday['a_intervaltwo'])
                ->select();
        $res = $this->smsBatch($vipData, input('sms'));
        $res = json_decode($res, true);
        if ($res['code'] == 200) {
            webApi(0, '', 0, ['yes' => $res['yes'], 'no' => $res['no']]);
        } else {
            webApi(1, $res['msg']);
        }
    }
}