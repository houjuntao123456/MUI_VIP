<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/14
 * Description J连带率
 */
class Loyrate extends Common
{
    /**
     * J连带率查表
     */
    public function index()
    {
        //获取分页数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($this->db.'.vip_rfm_j')
                    ->where('store_code', $search)
                    ->count();
            //查询
            $data = Db::table($this->db.'.vip_rfm_j')
                    ->where('store_code', $search)
                    ->order('j_create_time','desc') //按照登记时间降序排列
                    ->page($page, $limit)
                    ->select();
            //周期
            $j_consumption = Db::table($this->db.'.vip_rfm_days')->field('j_consumption')->where('store_code', $search)->find();
            // 查询人数需要条件
            $vips = Db::table($this->db.'.view_viplist')
                    ->where('store_code', $search)
                    ->where('rfm_days', '<=', $j_consumption['j_consumption'])
                    ->where('rfm_days', '>=', 0)
                    ->field('code')
                    ->select();
                    
            if ($vips) {
                $cards = array_column($vips, 'code');
            } else {
                $cards = [];
            }
            //查询出件数
            $fc_number = Db::table($this->db.'.vip_goods_order')->field('sum(number) as num')->where('vip_code', 'in', $cards)->where('status', 0)->group('vip_code')->select();
            
            //查询出次数
            $fc_frequency = Db::table($this->db.'.vip_goods_order')->field('count(id) as count')->where('vip_code', 'in', $cards)->where('status', 0)->group('vip_code')->select();
            
            if ($fc_number && $fc_frequency) {
                $fct = array_column($fc_number, 'num');
                $fctt = array_column($fc_frequency, 'count');
                $n = count($fc_number);
            } else {
                $fct = [];
                $fctt = [];
                $n = 0;
            }

            foreach($data as $k=>$v){
                //时间格式的转换
                $data[$k]['j_create_time_g'] = date('Y-m-d H:i:s', $v['j_create_time']);
                if ($data[$k]['j_update_time'] == "") {
                    $data[$k]['j_update_time_g'] = $data[$k]['j_create_time_g'];
                } else {
                    $data[$k]['j_update_time_g'] = date('Y-m-d H:i:s', $v['j_update_time']);
                }
                //连带率拼接
                $data[$k]['Index_interval'] =$data[$k]['j_intervalone'].' ≤ J < '.$data[$k]['j_intervaltwo'];
                //周期赋值    
                $data[$k]['j_consumption'] =  $j_consumption['j_consumption'];
                //会员人数
                if ($fct && $fctt) {
                    $c = 0;
                    for ($i = 0; $i < $n; $i++) {
                        //连带率 = 件数/次数
                        $jm = number_format($fct[$i]/$fctt[$i], 2);
                        if ($jm >= $v['j_intervalone'] && $jm < $v['j_intervaltwo']) {
                            $c++;
                        }
                    }
                    $data[$k]['numbertime'] = $c;
                } else {
                    $data[$k]['numbertime'] = 0;
                }
            }
            
            unset($page, $limit, $vips, $search, $j_consumption, $cards, $c, $fctt, $fct, $n, $fc_number, $fc_frequency);

        } else {
            $count = 0;
            $data = [];
        }

        webApi(0, '', $count, $data);
    }

    /**
     * 修改J类型
     */
    public function rateEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'j_intervalone' => input('j_intervalone'),
            'j_intervaltwo' => input('j_intervaltwo'),
            'j_update_time' => time(),
            'id' => $id
        ];
        //判断
        if($data['j_intervalone'] >= $data['j_intervaltwo']){
            webApi(0,'error',0,'区间(带率)不符合规则，前面不能大于等于后面！');
        }
        $this->Sectionjudge($data, $id, input('store_code'));
        $res = Db::table($this->db.'.vip_rfm_j')->update($data);
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
    public function rateDayEdit()
    {
        $data = [
            'j_consumption' => input('j_consumption')
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
        $meats = Db::table($this->db.'.vip_rfm_j')->where($where)->where('store_code', $store)->select();
        foreach ($meats as $v) {
            if (  ($up['j_intervalone'] >= $v['j_intervalone'] && $up['j_intervalone'] < $v['j_intervaltwo']) || ($up['j_intervaltwo'] > $v['j_intervalone'] && $up['j_intervaltwo'] <= $v['j_intervaltwo']) ) {
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
        $rday = Db::table($this->db.'.vip_rfm_j')->where('id', $id)->find();
        // 周期
        $j_consumption = Db::table($this->db.'.vip_rfm_days')->field('j_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('rate,v.*')
                ->where('v.store_code', $store)
                ->where('f.dates', '>=', time() - (86400 * $j_consumption['j_consumption']))
                ->where('f.vip_code','<>',0)
                ->where('f.rate', '>=', $rday['j_intervalone'])
                ->where('f.rate', '<', $rday['j_intervaltwo'])
                ->page($page, $limit)
                ->select();

        $count = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('rate,v.*')
                ->where('v.store_code', $store)
                ->where('f.dates', '>=', time() - (86400 * $j_consumption['j_consumption']))
                ->where('f.vip_code','<>',0)
                ->where('f.rate', '>=', $rday['j_intervalone'])
                ->where('f.rate', '<', $rday['j_intervaltwo'])
                ->count();

        unset($id, $page, $limit, $store, $rday, $j_consumption);        

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
        $rday = Db::table($this->db.'.vip_rfm_j')->where('id', $id)->find();
        // 周期
        $j_consumption = Db::table($this->db.'.vip_rfm_days')->field('j_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('rate,v.*')
                ->where('v.store_code', $store)
                ->where('f.dates', '>=', time() - (86400 * $j_consumption['j_consumption']))
                ->where('f.vip_code','<>',0)
                ->where('f.rate', '>=', $rday['j_intervalone'])
                ->where('f.rate', '<', $rday['j_intervaltwo'])
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