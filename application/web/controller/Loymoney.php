<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2018/08/15
 * Description M金额
 */
class Loymoney extends Common
{
    /**
     * M金额查表
     */
    public function index()
    {
        //获取分页数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($this->db.'.vip_rfm_m')
                    ->where('store_code', $search)
                    ->count();
            //查询
            $data = Db::table($this->db.'.vip_rfm_m')
                    ->where('store_code', $search)
                    ->order('m_create_time','desc') //按照登记时间降序排列
                    ->page($page, $limit)
                    ->select();
            //周期
            $m_consumption = Db::table($this->db.'.vip_rfm_days')->field('m_consumption')->where('store_code', $search)->find();
            // 查询人数需要条件
            $vips = Db::table($this->db.'.view_viplist')
                    ->where('store_code', $search)
                    ->where('rfm_days', '<=', $m_consumption['m_consumption'])
                    ->where('rfm_days', '>=', 0)
                    ->field('code')
                    ->select();

            if ($vips) {
                $cards = array_column($vips, 'code');
            } else {
                $cards = [];
            }

            $fc = Db::table($this->db.'.vip_goods_order')->field('sum(real_pay) as pay')->where('vip_code','in', $cards)->where('status', 0)->group('vip_code')->select();

            if ($fc) {
                $fct = array_column($fc, 'pay');
                $n = count($fc);
            } else {
                $fct = [];
                $n = 0;
            }

            foreach($data as $k=>$v){
                //时间格式的转换
                $data[$k]['m_create_time_g'] = date('Y-m-d H:i:s', $v['m_create_time']);
                if ($data[$k]['m_update_time'] == "") {
                    $data[$k]['m_update_time_g'] = $data[$k]['m_create_time_g'];
                } else {
                    $data[$k]['m_update_time_g'] = date('Y-m-d H:i:s', $v['m_update_time']);
                }
                //金额格式
                $data[$k]['m_intervalone_g'] = number_format($v['m_intervalone'], 2);
                $data[$k]['m_intervaltwo_g'] = number_format($v['m_intervaltwo'], 2);
                //拼成在表中的显示
                $data[$k]['Index_interval'] =$data[$k]['m_intervalone_g'].' ≤ M < '.$data[$k]['m_intervaltwo_g'];
                //周期赋值
                $data[$k]['m_consumption'] =  $m_consumption['m_consumption'];
                //人数
                if ($fct) {
                    $c = 0;
                    for ($i = 0; $i < $n; $i++) {
                        if ($fct[$i] >= $v['m_intervalone'] && $fct[$i] < $v['m_intervaltwo']) {
                            $c++;
                        }
                    }
                    $data[$k]['numbertime'] = $c;
                } else {
                    $data[$k]['numbertime'] = 0;
                }
            }

            unset($page, $limit, $search, $vips, $m_consumption, $cards, $c, $fc, $fct, $n);

        } else {
            $count = 0;
            $data = [];
        }

        webApi(0, '', $count, $data);
    }

    /**
     * 修改M类型
     */
    public function moneyEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'm_intervalone' => input('m_intervalone'),
            'm_intervaltwo' => input('m_intervaltwo'),
            'm_update_time' => time(),
            'id' => $id
        ];
        //判断
        if($data['m_intervalone'] >= $data['m_intervaltwo']){
            webApi(0,'error',0,'区间(金额)不符合规则，前面不能大于等于后面！');
        }
        $this->Sectionjudge($data, $id, input('store_code'));
        
        $res = Db::table($this->db.'.vip_rfm_m')->update($data);
        unset($data,$id);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 周期
     */
    public function moneyDayEdit()
    {
        $data = [
            'm_consumption' => input('m_consumption')
        ]; 
        $res = Db::table($this->db.'.vip_rfm_days')->where('store_code', input('store_code'))->update($data);
        unset($data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
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
        $meats = Db::table($this->db.'.vip_rfm_m')->where($where)->where('store_code', $store)->select();
        foreach ($meats as $v) {
            if (  ($up['m_intervalone'] >= $v['m_intervalone'] && $up['m_intervalone'] < $v['m_intervaltwo']) || ($up['m_intervaltwo'] > $v['m_intervalone'] && $up['m_intervaltwo'] <= $v['m_intervaltwo']) ) {
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
        $rday = Db::table($this->db.'.vip_rfm_m')->where('id', $id)->find();
        // 周期
        $m_consumption = Db::table($this->db.'.vip_rfm_days')->field('m_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('sum,v.*')
                ->where('f.dates', '>=', time() - (86400 * $m_consumption['m_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.sum', '>=', $rday['m_intervalone'])
                ->where('f.sum', '<', $rday['m_intervaltwo'])
                ->page($page, $limit)
                ->select();

        $count = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('sum,v.*')
                ->where('f.dates', '>=', time() - (86400 * $m_consumption['m_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.sum', '>=', $rday['m_intervalone'])
                ->where('f.sum', '<', $rday['m_intervaltwo'])
                ->count();

        unset($id, $page, $limit, $store, $rday, $m_consumption);        
        
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
        $rday = Db::table($this->db.'.vip_rfm_m')->where('id', $id)->find();
        // 周期
        $m_consumption = Db::table($this->db.'.vip_rfm_days')->field('m_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('sum,v.*')
                ->where('f.dates', '>=', time() - (86400 * $m_consumption['m_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.sum', '>=', $rday['m_intervalone'])
                ->where('f.sum', '<', $rday['m_intervaltwo'])
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