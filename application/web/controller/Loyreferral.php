<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/14
 * Description I转介数
 */
class Loyreferral extends Common
{
    /**
     * I转介数查表
     */
    public function index()
    {
        //获取分页数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];
        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($this->db.'.vip_rfm_i')
                    ->where('store_code', $search)
                    ->count();
            //查询
            $data = Db::table($this->db.'.vip_rfm_i')
                    ->where('store_code', $search)
                    ->order('i_score', 'asc') //按照登记时间降序排列
                    ->page($page, $limit)
                    ->select();
            // 周期
            $i_consumption = Db::table($this->db.'.vip_rfm_days')->field('i_consumption')->where('store_code', $search)->find();

            $field = 'count(IF(`count` >= '.$data[1]['i_intervalone'].' AND `count` < '.$data[1]['i_intervaltwo'].', `count`, null)) as one,'.
                    'count(IF(`count` >= '.$data[2]['i_intervalone'].' AND `count` < '.$data[2]['i_intervaltwo'].', `count`, null)) as two,'.
                    'count(IF(`count` >= '.$data[3]['i_intervalone'].' AND `count` < '.$data[3]['i_intervaltwo'].', `count`, null)) as three,'.
                    'count(IF(`count` >= '.$data[4]['i_intervalone'].' AND `count` < '.$data[4]['i_intervaltwo'].', `count`, null)) as four,'.
                    'count(IF(`count` >= '.$data[5]['i_intervalone'].' AND `count` < '.$data[5]['i_intervaltwo'].', `count`, null)) as five,'.
                    'count(*) as total';
    
            $referral = Db::table($this->db.'.view_referral')
                        ->field($field)
                        ->where('lnttime', '>=', time() - ( $i_consumption['i_consumption'] * 86400))
                        ->find();

            $vip = Db::table($this->db.'.vip_viplist')->where('store_code', $search)->count();

            foreach($data as $k=>$v){
                //时间格式的转换
                $data[$k]['i_create_time_g'] = date('Y-m-d H:i:s', $v['i_create_time']);
                if ($data[$k]['i_update_time'] == "") {
                    $data[$k]['i_update_time_g'] = $data[$k]['i_create_time_g'];
                } else {
                    $data[$k]['i_update_time_g'] = date('Y-m-d H:i:s', $v['i_update_time']);
                }
                //转介数拼接
                $data[$k]['Index_interval'] = $data[$k]['i_intervalone'].' ≤ I < '.$data[$k]['i_intervaltwo'];
                //给周期赋值
                $data[$k]['i_consumption'] =  $i_consumption['i_consumption'];
                //人数
                switch ($v['i_score']) {
                    case 0:
                        $data[$k]['numbertime'] = $vip - $referral['total'];
                    break;
                    case 1:
                        $data[$k]['numbertime'] = $referral['one'];
                    break;
                    case 2:
                        $data[$k]['numbertime'] = $referral['two'];
                    break;
                    case 3:
                        $data[$k]['numbertime'] = $referral['three'];
                    break;
                    case 4:
                        $data[$k]['numbertime'] = $referral['four'];
                    break;
                    case 5:
                        $data[$k]['numbertime'] = $referral['five'];
                    break;
                }
            }
            unset($page, $limit, $search, $vip, $i_consumption, $referral, $field);

        } else {
            $count = 0;
            $data = []; 
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 修改I类型
     */
    public function referralEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $data = [
            'i_intervalone' => input('i_intervalone'),
            'i_intervaltwo' => input('i_intervaltwo'),
            'i_update_time' => time(),
            'id' => $id
        ];

        //判断
        if($data['i_intervalone'] >= $data['i_intervaltwo']){
            webApi(0,'error',0,'区间(转介数)不符合规则，前面不能大于等于后面！');
        }
        $this->Sectionjudge($data, $id, input('store_code'));

        $res = Db::table($this->db.'.vip_rfm_i')->update($data);
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
    public function referralDayEdit()
    {
        $data = [
            'i_consumption' => input('i_consumption')
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
        $meats = Db::table($this->db.'.vip_rfm_i')->where($where)->where('store_code', $store)->select();
        foreach ($meats as $v) {
            if (  ($up['i_intervalone'] >= $v['i_intervalone'] && $up['i_intervalone'] < $v['i_intervaltwo']) || ($up['i_intervaltwo'] > $v['i_intervalone'] && $up['i_intervaltwo'] <= $v['i_intervaltwo']) ) {
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
        $rday = Db::table($this->db.'.vip_rfm_i')->where('id', $id)->find();
        // 周期
        $i_consumption = Db::table($this->db.'.vip_rfm_days')->field('i_consumption')->where('store_code', $store)->find();
        //数据
        if ($rday['i_type'] !== '无') {
            $vipData = Db::table($this->db.'.view_viplist')
                    ->alias('v')
                    ->leftJoin($this->db.'.view_referral r', 'r.lnt_code = v.code')
                    ->field('r.count,v.*')
                    ->where('v.store_code', $store)
                    ->where('r.lnt_code','<>',0)
                    ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                    ->where('r.count', '>=', $rday['i_intervalone'])
                    ->where('r.count', '<', $rday['i_intervaltwo'])
                    ->page($page, $limit)
                    ->select();
            $count = Db::table($this->db.'.view_viplist')
                    ->alias('v')
                    ->leftJoin($this->db.'.view_referral r', 'r.lnt_code = v.code')
                    ->where('v.store_code', $store)
                    ->where('r.lnt_code','<>',0)
                    ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                    ->where('r.count', '>=', $rday['i_intervalone'])
                    ->where('r.count', '<', $rday['i_intervaltwo'])
                    ->count();
        } else {
            $limitStart = $limit * ($page - 1);
            $vipData = Db::query('select * from '.$this->db.'.view_viplist where code not in(select lnt_code from '.$this->db.'.view_referral) and store_code = \''.$store.'\' limit '.$limitStart.','.$limit);
            $count = Db::query('select count(id) as count from '.$this->db.'.view_viplist where code not in(select lnt_code from '.$this->db.'.view_referral) and store_code = \''.$store.'\'');
            $count = $count[0]['count'];
        }
        
        unset($id, $page, $limit, $store, $rday, $i_consumption, $limitStart);

        webApi(0, '', $count, $vipData);
    }

    //短信
    public function smsSend()
    {
        //获取所需数据
        [$id, $store] = [input('id'), input('store_code')];

        if ($id == null) {
            webApi(1, '参数错误！');
        }
        //限制
        $rday = Db::table($this->db.'.vip_rfm_i')->where('id', $id)->find();
        // 周期
        $i_consumption = Db::table($this->db.'.vip_rfm_days')->field('i_consumption')->where('store_code', $store)->find();
        //数据
        if ($id > 1) {
            $vipData = Db::table($this->db.'.view_viplist')
                    ->alias('v')
                    ->leftJoin($this->db.'.view_referral r', 'r.lnt_code = v.code')
                    ->field('r.count,v.*')
                    ->where('v.store_code', $store)
                    ->where('r.lnt_code','<>',0)
                    ->where('r.lnttime', '>=', time() - (86400 * $i_consumption['i_consumption']))
                    ->where('r.count', '>=', $rday['i_intervalone'])
                    ->where('r.count', '<', $rday['i_intervaltwo'])
                    ->select();
        } else {
            $vipData = Db::query('select * from '.$this->db.'.view_viplist where code not in(select lnt_code from '.$this->db.'.view_referral) and store_code = \''.$store.'\'');
        }
        $res = $this->smsBatch($vipData, input('sms'));
        $res = json_decode($res, true);
        if ($res['code'] == 200) {
            webApi(0, '', 0, ['yes' => $res['yes'], 'no' => $res['no']]);
        } else {
            webApi(1, $res['msg']);
        }
    }
}