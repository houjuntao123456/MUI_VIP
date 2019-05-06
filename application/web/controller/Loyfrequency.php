<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/14
 * Description F次数
 */
class Loyfrequency extends Common
{
    /**
     * F次数查表
     */
    public function index()
    {
        //获取分页数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($this->db.'.vip_rfm_f')
                    ->where('store_code', $search)
                    ->count();
            //查询
            $data = Db::table($this->db.'.vip_rfm_f')
                    ->where('store_code', $search)
                    ->order('f_create_time','desc') //按照登记时间降序排列
                    ->page($page, $limit)
                    ->select();
            //查询周期
            $f_consumption = Db::table($this->db.'.vip_rfm_days')->where('store_code', $search)->field('f_consumption')->find();
            //查询人数需要的条件
            //查询会员未消费天数大于零且小于等于周期的卡号
            $vips = Db::table($this->db.'.view_viplist')
                    ->where('store_code', $search)
                    ->where('rfm_days', '<=', $f_consumption['f_consumption'])
                    ->where('rfm_days', '>=', 0)
                    ->field('code')
                    ->select();

            if ($vips) {
                $cards = array_column($vips, 'code');
            } else {
                $cards = [];
            }

            $fc = Db::table($this->db.'.vip_goods_order')->field('count(id) as count')->where('vip_code', 'in', $cards)->where('status', 0)->group('vip_code')->select();

            if ($fc) {
                $fct = array_column($fc, 'count');
                $n = count($fct);
            } else {
                $fct = [];
                $n = 0;
            }

            foreach($data as $k=>$v){
                //时间格式的转换
                $data[$k]['f_create_time_g'] = date('Y-m-d H:i:s', $v['f_create_time']);
                if ($data[$k]['f_update_time'] == "") {
                    $data[$k]['f_update_time_g'] = $data[$k]['f_create_time_g'];
                } else {
                    $data[$k]['f_update_time_g'] = date('Y-m-d H:i:s', $v['f_update_time']);
                }
                //次数拼接
                $data[$k]['Index_interval'] = $data[$k]['f_intervalone'].' ≤ F < '.$data[$k]['f_intervaltwo'];
                //赋值周期
                $data[$k]['f_consumption'] =  $f_consumption['f_consumption'];
                //人数
                if ($v['id'] == 1) {
                    //查询大于未消费天数且已消费的人数
                    $data[$k]['numbertime'] = Db::table($this->db.'.view_viplist')->where('final_purchases', '<>', '未消费')->where('rfm_days', '>', $f_consumption['f_consumption'])->count();
                } else {
                    if ($fct) {
                        $c = 0;
                        for ($i = 0; $i < $n; $i++) {
                            if ($fct[$i] >= $v['f_intervalone'] && $fct[$i] < $v['f_intervaltwo']) {
                                $c++;
                            }
                        }
                        $data[$k]['numbertime'] = $c;
                    } else {
                        $data[$k]['numbertime'] = 0;
                    }
                }
            }

            unset($page, $limit, $search, $vips, $f_consumption, $cards, $c, $fc, $fct, $n);

        } else {
            $count = 0;
            $data = [];
        }
        
        webApi(0, '', $count, $data);
    }

    /**
     * 修改F类型
     */
    public function frequencyEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'f_intervalone' => input('f_intervalone'),
            'f_intervaltwo' => input('f_intervaltwo'),
            'f_update_time' => time(),
            'id' => $id
        ];
        //判断
        if($data['f_intervalone'] >= $data['f_intervaltwo']){
            webApi(0,'error',0,'区间(次数)不符合规则，前面不能大于等于后面！');
        }
        $this->Sectionjudge($data, $id, input('store_code'));
        $res = Db::table($this->db.'.vip_rfm_f')->update($data);
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
    public function frequencyDayEdit()
    {
        $data = [
            'f_consumption' => input('f_consumption')
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
        $meats = Db::table($this->db.'.vip_rfm_f')->where($where)->where('store_code', $store)->select();
        foreach ($meats as $v) {
            if (  ($up['f_intervalone'] >= $v['f_intervalone'] && $up['f_intervalone'] < $v['f_intervaltwo']) || ($up['f_intervaltwo'] > $v['f_intervalone'] && $up['f_intervaltwo'] <= $v['f_intervaltwo']) ) {
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
        $rday = Db::table($this->db.'.vip_rfm_f')->where('id', $id)->find();
        // 周期
        $f_consumption = Db::table($this->db.'.vip_rfm_days')->where('store_code', $store)->field('f_consumption')->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('count,v.*')
                ->where('f.dates', '>=', time() - (86400 * $f_consumption['f_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.count', '>=', $rday['f_intervalone'])
                ->where('f.count', '<', $rday['f_intervaltwo'])
                ->page($page, $limit)
                ->select();

        $count = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('count,v.*')
                ->where('f.dates', '>=', time() - (86400 * $f_consumption['f_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.count', '>=', $rday['f_intervalone'])
                ->where('f.count', '<', $rday['f_intervaltwo'])
                ->count();

        unset($id, $page, $limit, $store, $rday, $f_consumption);        

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
        $rday = Db::table($this->db.'.vip_rfm_f')->where('id', $id)->find();
        // 周期
        $f_consumption = Db::table($this->db.'.vip_rfm_days')->where('store_code', $store)->field('f_consumption')->find();
        //数据
        $vipData = Db::table($this->db.'.view_rfm_member')
                ->alias('f')
                ->leftJoin($this->db.'.view_viplist v', 'v.code = f.vip_code')
                ->field('count,v.*')
                ->where('f.dates', '>=', time() - (86400 * $f_consumption['f_consumption']))
                ->where('v.store_code', $store)
                ->where('f.vip_code','<>',0)
                ->where('f.count', '>=', $rday['f_intervalone'])
                ->where('f.count', '<', $rday['f_intervaltwo'])
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