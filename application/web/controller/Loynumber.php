<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/14
 * Description N件数
 */
class Loynumber extends Common
{
    /**
     * N件数查表
     */
    public function index()
    {
        //获取分页数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($this->db.'.vip_rfm_n')
                    ->where('store_code', $search)
                    ->count();
            //查询
            $data = Db::table($this->db.'.vip_rfm_n')
                    ->where('store_code', $search)
                    ->order('n_create_time','desc') //按照登记时间降序排列
                    ->page($page, $limit)
                    ->select();
            //周期
            $n_consumption = Db::table($this->db.'.vip_rfm_days')->field('n_consumption')->where('store_code', $search)->find();
            //查询人数需要的条件
            //查询会员未消费天数大于零且小于等于周期的卡号
            $vips = Db::table($this->db.'.view_viplist')
                    ->where('store_code', $search)
                    ->where('rfm_days', '<=', $n_consumption['n_consumption'])
                    ->where('rfm_days', '>=', 0)
                    ->field('code')
                    ->select();

            if ($vips) {
                $cards = array_column($vips, 'code');
            } else {
                $cards = [];
            }

            $fc = Db::table($this->db.'.vip_goods_order')->field('sum(number) as num')->where('vip_code', 'in', $cards)->where('status', 0)->group('vip_code')->select();

            if ($fc) {
                $fct = array_column($fc, 'num');
                $n = count($fct);
            } else {
                $fct = [];
                $n = 0;
            }

            foreach($data as $k=>$v){
                //时间格式的转换
                $data[$k]['n_create_time_g'] = date('Y-m-d H:i:s', $v['n_create_time']);
                if ($data[$k]['n_update_time'] == "") {
                    $data[$k]['n_update_time_g'] = $data[$k]['n_create_time_g'];
                } else {
                    $data[$k]['n_update_time_g'] = date('Y-m-d H:i:s', $v['n_update_time']);
                }
                //件数拼接
                $data[$k]['Index_interval'] =$data[$k]['n_intervalone'].' ≤ N < '.$data[$k]['n_intervaltwo'];
                //周期赋值
                $data[$k]['n_consumption'] =  $n_consumption['n_consumption'];
                //人数
                if ($v['id'] == 1) {
                    //查询大于未消费天数且已消费的人数
                    $data[$k]['numbertime'] = Db::table($this->db.'.view_viplist')->where('final_purchases', '<>', '未消费')->where('rfm_days', '>', $n_consumption['n_consumption'])->count();
                } else {
                    if ($fct) {
                        $c = 0;
                        for ($i = 0; $i < $n; $i++) {
                            if ($fct[$i] >= $v['n_intervalone'] && $fct[$i] < $v['n_intervaltwo']) {
                                $c++;
                            }
                        }
                        $data[$k]['numbertime'] = $c;
                    } else {
                        $data[$k]['numbertime'] = 0;
                    }
                }
            }
            
            unset($page, $limit, $search, $vips, $n_consumption, $cards, $c, $fct, $n, $fc);

        } else {
            $count = 0;
            $data = [];
        }

        webApi(0, '', $count, $data);
    }

    /**
     * 修改N类型
     */
    public function numberEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'n_intervalone' => input('n_intervalone'),
            'n_intervaltwo' => input('n_intervaltwo'),
            'n_update_time' => time(),
            'id' => $id
        ];
        //判断
        if($data['n_intervalone'] >= $data['n_intervaltwo']){
            webApi(0,'error',0,'区间(件数)不符合规则，前面不能大于等于后面！');
        }
        $this->Sectionjudge($data, $id, input('store_code'));
        $res = Db::table($this->db.'.vip_rfm_n')->update($data);
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
    public function numberDayEdit()
    {
        $data = [
            'n_consumption' => input('n_consumption')
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
        $meats = Db::table($this->db.'.vip_rfm_n')->where($where)->where('store_code', $store)->select();
        foreach ($meats as $v) {
            if (  ($up['n_intervalone'] >= $v['n_intervalone'] && $up['n_intervalone'] < $v['n_intervaltwo']) || ($up['n_intervaltwo'] > $v['n_intervalone'] && $up['n_intervaltwo'] <= $v['n_intervaltwo']) ) {
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
        $rday = Db::table($this->db.'.vip_rfm_n')->where('id', $id)->find();
        // 周期
        $n_consumption = Db::table($this->db.'.vip_rfm_days')->field('n_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('num,v.*')
                ->where('f.dates', '>=', time() - (86400 * $n_consumption['n_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.num', '>=', $rday['n_intervalone'])
                ->where('f.num', '<', $rday['n_intervaltwo'])
                ->page($page, $limit)
                ->select();

        $count = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('num,v.*')
                ->where('f.dates', '>=', time() - (86400 * $n_consumption['n_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.num', '>=', $rday['n_intervalone'])
                ->where('f.num', '<', $rday['n_intervaltwo'])
                ->count();

        unset($id, $page, $limit, $store, $rday, $n_consumption);    
            
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
        $rday = Db::table($this->db.'.vip_rfm_n')->where('id', $id)->find();
        // 周期
        $n_consumption = Db::table($this->db.'.vip_rfm_days')->field('n_consumption')->where('store_code', $store)->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('num,v.*')
                ->where('f.dates', '>=', time() - (86400 * $n_consumption['n_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.num', '>=', $rday['n_intervalone'])
                ->where('f.num', '<', $rday['n_intervaltwo'])
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