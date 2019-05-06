<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/14
 * Description C年消费
 */
class Loyannual extends Common
{
    /**
     * C年消费查表
     */
    public function index()
    {
        //获取分页数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($this->db.'.vip_rfm_c')
                    ->where('store_code', $search)
                    ->count();
            //查询
            $data = Db::table($this->db.'.vip_rfm_c')
                    ->where('store_code', $search)
                    ->order('c_create_time','desc') //按照登记时间降序排列
                    ->page($page, $limit)
                    ->select();
            //周期
            $c_consumption = Db::table($this->db.'.vip_rfm_days')->field('c_consumption')->where('store_code', $search)->find();
            // 查询人数需要条件
            $vips = Db::table($this->db.'.view_viplist')
                    ->where('store_code', $search)
                    ->where('rfm_days', '<=', $c_consumption['c_consumption'])
                    ->where('rfm_days', '>=', 0)
                    ->field('code')
                    ->select();

            if ($vips) {
                $cards = array_column($vips, 'code');
            } else {
                $cards = [];
            }

            $fc = Db::table($this->db.'.vip_goods_order')
                ->field('sum(real_pay) as pay')
                ->where('vip_code','in', $cards)
                ->where('status', 0)
                ->group('vip_code')
                ->select();

            if ($fc) {
                $fct = array_column($fc, 'pay');
                $n = count($fc);
            } else {
                $fct = [];
                $n = 0;
            }

            foreach($data as $k=>$v){
                //时间格式的转换
                $data[$k]['c_create_time_g'] = date('Y-m-d H:i:s', $v['c_create_time']);
                if ($data[$k]['c_update_time'] == "") {
                    $data[$k]['c_update_time_g'] = $data[$k]['c_create_time_g'];
                } else {
                    $data[$k]['c_update_time_g'] = date('Y-m-d H:i:s', $v['c_update_time']);
                }
                //金额格式
                $data[$k]['c_intervalone_g'] = number_format($v['c_intervalone'], 2);
                $data[$k]['c_intervaltwo_g'] = number_format($v['c_intervaltwo'], 2);
                //拼成在表中的显示
                $data[$k]['Index_interval'] = $data[$k]['c_intervalone_g'].' ≤ C < '.$data[$k]['c_intervaltwo_g'];
                //周期赋值
                $data[$k]['c_consumption'] =  $c_consumption['c_consumption'];
                //人数
                if ($fct) {
                    $c = 0;
                    for ($i = 0; $i < $n; $i++) {
                        if ($fct[$i] >= $v['c_intervalone'] && $fct[$i] < $v['c_intervaltwo']) {
                            $c++;
                        }
                    }
                    $data[$k]['numbertime'] = $c;
                } else {
                    $data[$k]['numbertime'] = 0;
                }
            }
            
            unset($page, $limit, $search, $vips, $c_consumption, $cards, $c, $fc, $fct, $n);

        } else {
            $count = 0;
            $data = [];
        }

        webApi(0, '', $count, $data);
    }

    /**
     * 修改C类型
     */
    public function annualEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'c_intervalone' => input('c_intervalone'),
            'c_intervaltwo' => input('c_intervaltwo'),
            'c_update_time' => time(),
            'id' => $id
        ];
        //判断
        if($data['c_intervalone'] >= $data['c_intervaltwo']){
            webApi(0,'error',0,'区间(金额)不符合规则，前面不能大于等于后面！');
        }
        $this->Sectionjudge($data, $id, input('store_code'));

        $res = Db::table($this->db.'.vip_rfm_c')->update($data);
        unset($id,$data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 修改周期
     */
    public function annualDayEdit()
    {
        $data = [
            'c_consumption' => input('c_consumption')
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
        $meats = Db::table($this->db.'.vip_rfm_c')->where($where)->where('store_code', $store)->select();
        foreach ($meats as $v) {
            if (  ($up['c_intervalone'] >= $v['c_intervalone'] && $up['c_intervalone'] < $v['c_intervaltwo']) || ($up['c_intervaltwo'] > $v['c_intervalone'] && $up['c_intervaltwo'] <= $v['c_intervaltwo']) ) {
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
        $rday = Db::table($this->db.'.vip_rfm_c')->where('id', $id)->find();
        // 周期
        $c_consumption = Db::table($this->db.'.vip_rfm_days')->field('c_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('sum,v.*')
                ->where('f.dates', '>=', time() - (86400 * $c_consumption['c_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.sum', '>=', $rday['c_intervalone'])
                ->where('f.sum', '<', $rday['c_intervaltwo'])
                ->page($page, $limit)
                ->select();

        $count = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('sum,v.*')
                ->where('f.dates', '>=', time() - (86400 * $c_consumption['c_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.sum', '>=', $rday['c_intervalone'])
                ->where('f.sum', '<', $rday['c_intervaltwo'])
                ->count();

        unset($id, $page, $limit, $store, $rday, $c_consumption);    

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
        $rday = Db::table($this->db.'.vip_rfm_c')->where('id', $id)->find();
        // 周期
        $c_consumption = Db::table($this->db.'.vip_rfm_days')->field('c_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('sum,v.*')
                ->where('f.dates', '>=', time() - (86400 * $c_consumption['c_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.sum', '>=', $rday['c_intervalone'])
                ->where('f.sum', '<', $rday['c_intervaltwo'])
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