<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author lhp
 * Date 2018/12/28
 * Description 会员权益 --- 晋升标准
 */
class Promote extends Common
{
    /**
     * 晋升列表
     */
    public function promotes()
    {
        //分页信息
        [$page, $limit, $lookup] = [input('page'), input('limit'), input('lookup')];
        
        //统计数量
        $count = Db::table($this->db.'.vip_vippromote')->count();

        //模糊查询
        if ($lookup) {
            $where[] = ['vg.username', 'like', '%' . $lookup . '%'];
        } else {
            $where = true;
        }
        unset($lookup);
        //查询
        $data = Db::table($this->db.'.vip_vippromote')
            ->alias('v')
            ->leftJoin($this->db.'.vip_viplevel vg', 'vg.code = v.levelname')
            ->field('vg.username vgname, v.id, v.number, v.levelname, v.introduction, v.introduction_time, v.total_amount, v.total_amount_time, v.total_integral, v.total_integral_time, v.first_amount, v.single_amount, v.single_recharge, v.state, v.notification, v.org_code')
            ->where($where)
            ->order('v.number')
            ->page($page, $limit)
            ->select();
            unset($page, $limit);
        //修改格式
        foreach ($data as $k => $v) {
            $data[$k]['total_integral'] == 99999999999 ? $data[$k]['total_integral'] = '无规则' : $data[$k]['total_integral']; 
            $data[$k]['total_integral_time'] == 99999999999 ? $data[$k]['total_integral_time'] = '无规则' : $data[$k]['total_integral_time']; 
            $data[$k]['total_amount'] == 99999999999 ? $data[$k]['total_amount'] = '无规则' : $data[$k]['total_amount']; 
            $data[$k]['total_amount_time'] == 99999999999 ? $data[$k]['total_amount_time'] = '无规则' : $data[$k]['total_amount_time']; 
            $data[$k]['first_amount'] == 99999999999 ? $data[$k]['first_amount'] = '无规则' : $data[$k]['first_amount']; 
            $data[$k]['single_amount'] == 99999999999 ? $data[$k]['single_amount'] = '无规则' : $data[$k]['single_amount']; 
            $data[$k]['single_recharge'] == 99999999999 ? $data[$k]['single_recharge'] = '无规则' : $data[$k]['single_recharge']; 
            $data[$k]['introduction'] == 99999999999 ? $data[$k]['introduction'] = '无规则' : $data[$k]['introduction']; 
            $data[$k]['introduction_time'] == 99999999999 ? $data[$k]['introduction_time'] = '无规则' : $data[$k]['introduction_time']; 
            $data[$k]['state'] == 1 ? $data[$k]['state'] = '是' : $data[$k]['state'] = '否'; //自动升级
            $data[$k]['notification'] == 1 ? $data[$k]['notification'] = '开启' : $data[$k]['notification'] = '关闭'; //短信通知
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 会员服务 添加功能
     */
    public function promoteadd()
    {
        $up = [
            'number' => input('number'),//编号
            'levelname' => input('levelname'),//级别名称
            'state' => input('state') !== null ? 1 : 0, //晋升标准
            'notification' => input('notification') !== null ? 1 : 0
        ];
        
        if (input('total_integral') != '') {
            $up['total_integral'] = input('total_integral');
        }

        if (input('total_integral_time') != '') {
            $up['total_integral_time'] = input('total_integral_time');
        }

        if (input('total_amount') != '') {
            $up['total_amount'] = input('total_amount');
        }

        if (input('total_amount_time') != '') {
            $up['total_amount_time'] = input('total_amount_time');
        }

        if (input('first_amount') != '') {
            $up['first_amount'] = input('first_amount');
        }

        if (input('single_amount') != '') {
            $up['single_amount'] = input('single_amount');
        }

        if (input('single_recharge') != '') {
            $up['single_recharge'] = input('single_recharge');
        }

        if (input('introduction') != '') {
            $up['introduction'] = input('introduction');
        }

        if (input('introduction_time') != '') {
            $up['introduction_time'] = input('introduction_time');
        }

        $orgs = explode(',', input('splb_dtrr_id'));
        $insertData = [];
        for ($i = 0; $i < count($orgs); $i++) {
            $insertData[$i] = $up;
            $insertData[$i]['org_code'] = $orgs[$i];
        }
        
        //验证场景
        // $result = $this->validate($up, 'app\web\validate\v1\PromoteValidate.promoteAE');
        // if (true !== $result) {
        //     webApi(0, 'error', 0, $result);
        // }
        // unset($result);

        //编号是否存在
        // $cardrepeat = Db::table($this->db.'.vip_vippromote')->where('number', $up['number'])->find();
        // if ($cardrepeat) {
        //     webApi(0, 'error', 0, '编号已存在!');
        // }
        //修改数据库信息
        $res = Db::table($this->db.'.vip_vippromote')->insertAll($insertData);
        unset($up);
           
        //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '添加信息成功!');
        } else {
            webApi(0, 'no', 0, '添加信息失败!');
        }
    }

    /**
     * 工具条删除功能
     */
    public function promotedel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $res = Db::table($this->db.'.vip_vippromote')->delete($id);
        unset($id);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 工具条编辑功能
     */
    public function promoteedit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        // $this->actionLock(input('access_token'), request()->controller().'_'.request()->action(), $id);

        $data = [
            'id' => $id,
            'number' => input('number'),//编号
            'levelname' => input('levelname'),//级别名称
            'total_integral' => input('total_integral') == '' ? 99999999999 : input('total_integral'), //累计总积分
            'total_integral_time' => input('total_integral_time') == '' ? 99999999999 : input('total_integral_time'),//限定天数
            'total_amount' => input('total_amount') == '' ? 99999999999 : input('total_amount'),//消费总金额
            'total_amount_time' => input('total_amount_time') == '' ? 99999999999 : input('total_amount_time'),//限定天数
            'first_amount' => input('first_amount') == '' ? 99999999999 : input('first_amount'),//首次消费金额
            'single_amount' => input('single_amount') == '' ? 99999999999 : input('single_amount'), //单次消费金额
            'single_recharge' => input('single_recharge') == '' ? 99999999999 : input('single_recharge'),//单次充值金额
            'introduction' => input('introduction') == '' ? 99999999999 : input('introduction'),//转介绍人数
            'introduction_time' => input('introduction_time') == '' ? 99999999999 : input('introduction_time'),//限定天数
            'state' => input('state') !== null ? 1 : 0, //晋升标准
            'notification' => input('notification') !== null ? 1 : 0,//短信通知
            'org_code' => input('org_code') //组织机构
        ];

        //验证场景
        // $result = $this->validate($data, 'app\web\validate\v1\PromoteValidate.promoteAE');
        // if (true !== $result) {
        //     $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //     webApi(0, 'error', 0, $result);
        // }
        // unset($result);
        
        //修改数据库信息
        $res = Db::table($this->db.'.vip_vippromote')->update($data);
        unset($data);
        // $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);

        //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '修改信息成功!');
        } else {
            webApi(0, 'no', 0, '修改信息失败!');
        }
    }

    /**
     * 批量删除
     */
    public function delMany()
    {
        if (input('ids') == '') {
            webApi(1, '参数错误');
        }
        
        $ids = trim(input('ids'), ',');
        $res = Db::table($this->db.'.vip_vippromote')->where('id', 'in', $ids)->delete();
        unset($ids);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

    /**
     * 查询会员级别下拉框信息
     */
    public function drop_level()
    {
        $res = Db::table($this->db.'.vip_viplevel')->field('code,username')->select();
        webApi(0, '', 0, $res);
    }

}