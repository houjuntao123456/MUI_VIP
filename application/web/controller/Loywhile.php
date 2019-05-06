<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/14
 * Description R时间
 */
class Loywhile extends Common
{
    /**
     * 查询R时间表
     */
    public function index()
    {
        //获取分页数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];

        //模糊查询
        if ($search != '') {
            //统计数量
            $count = Db::table($this->db.'.vip_rfm_r')
                    ->where('store_code', $search)
                    ->count();
            //查询
            $data = Db::table($this->db.'.vip_rfm_r')
                    ->where('store_code', $search)
                    ->order('r_create_time','desc') //按照登记时间降序排列
                    ->page($page, $limit)
                    ->select();

            $vipData = Db::table($this->db.'.view_viplist')->where('store_code', $search)->field('rfm_days')->select();

            foreach ($data as $k => $v) {
                //时间格式的转换
                $data[$k]['r_create_time_g'] = date('Y-m-d H:i:s', $v['r_create_time']);
                if ($data[$k]['r_update_time'] == "") {
                    $data[$k]['r_update_time_g'] = $data[$k]['r_create_time_g'];
                } else {
                    $data[$k]['r_update_time_g'] = date('Y-m-d H:i:s', $v['r_update_time']);
                }
                $data[$k]['Index_interval'] = $data[$k]['r_intervalone'].' - '.$data[$k]['r_intervaltwo'];
                $data[$k]['numbertime'] = 0;
            }

            foreach ($data as $k=>$v) {
                foreach ($vipData as $val) {
                    if ($v['r_intervalone'] <= $val['rfm_days'] && $v['r_intervaltwo'] > $val['rfm_days']) {
                        $data[$k]['numbertime'] += 1;
                    }
                }
            }

            unset($page, $limit, $search, $vipData);
            
        } else {
            $count = 0;
            $data = [];
        }

        webApi(0, '', $count, $data);
    }

    /**
     * 修改R类型
     */
    public function whileEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'r_intervalone' => input('r_intervalone'),
            'r_intervaltwo' => input('r_intervaltwo'),
            'r_update_time' => time(),
            'id' => $id
        ];

        if($data['r_intervalone'] >= $data['r_intervaltwo']){
            webApi(0,'error',0,'区间(天数)不符合规则，前面不能大于等于后面！');
        }
        $this->Sectionjudge($data, $id, input('store_code'));
       
        $res = Db::table($this->db.'.vip_rfm_r')->update($data);
        unset($id,$data);

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
        $meats = Db::table($this->db.'.vip_rfm_r')->where($where)->where('store_code', $store)->select();
        foreach ($meats as $v) {
            if (  ($up['r_intervalone'] >= $v['r_intervalone'] && $up['r_intervalone'] < $v['r_intervaltwo']) || ($up['r_intervaltwo'] > $v['r_intervalone'] && $up['r_intervaltwo'] <= $v['r_intervaltwo']) ) {
                webApi(0, 'no', 0, '该区间规则已存在！');
            }
        }
    }

    /**
     * 查人数
     */
    public function lookPeople()
    {
        // 获取分页数据
        [$page, $limit, $id, $store] = [input('page'), input('limit'), input('id'), input('store_code')];

        if ($id == null) {
            webApi(1, '参数错误！');
        }
        
        $rday = Db::table($this->db.'.vip_rfm_r')->where('id', $id)->find();

        $count = Db::table($this->db.'.view_viplist')
                ->where('store_code', $store)
                ->where('rfm_days', '>=', $rday['r_intervalone'])
                ->where('rfm_days', '<', $rday['r_intervaltwo'])
                ->count();

        $vipData = Db::table($this->db.'.view_viplist')
                ->where('store_code', $store)
                ->where('rfm_days', '>=', $rday['r_intervalone'])
                ->where('rfm_days', '<', $rday['r_intervaltwo'])
                ->page($page,$limit)
                ->select();

        unset($id, $page, $limit, $rday, $store);

        webApi(0, '', $count, $vipData);
    }

    //短信
    public function smsSend()
    {
        // 获取分页数据
        [$page, $limit, $id, $store] = [input('page'), input('limit'), input('id'), input('store_code')];

        if ($id == null) {
            webApi(1, '参数错误！');
        }
        
        $rday = Db::table($this->db.'.vip_rfm_r')->where('id', $id)->find();
        $vipData = Db::table($this->db.'.view_viplist')
                ->where('store_code', $store)
                ->where('rfm_days', '>=', $rday['r_intervalone'])
                ->where('rfm_days', '<', $rday['r_intervaltwo'])
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