<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author hrl
 * Date 2018/1/16
 * Description STM员工管理 --- V票资金来源
 */
class Vcapital extends Common
{
    /**
     * 乐捐列表
     */
    public function index()
    {
        // 获取分页数据
        [$page, $limit] = [input('page'), input('limit')];
        // 搜索
        if (input('search') != '') {
            $where[] = ['name', 'like', '%'.input('search').'%'];
        } else {
            $where = true; // 永真条件 等于 没有条件
        }
        //查询
        $data = Db::table($this->db.'.vip_vcapital')
                ->alias('v')
                ->leftJoin($this->db.'.vip_staff f', 'f.code = v.capital_code')
                ->field('v.*,f.name')
                ->where($where)
                ->order('v.capital_time', 'desc')
                ->page($page, $limit)
                ->select();
        unset($page, $limit);
        //统计数量
        $count = Db::table($this->db.'.vip_vcapital')->count();
         //修改格式
        foreach ($data as $k => $v) {
            $data[$k]['capital_time'] = date('Y-m-d H:i', $v['capital_time']);
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 乐捐人列表
     * 1.只能选择自己和该规则里面的人
     */
    public function vcapitalStaff()
    {
        // 选择规则 获得规则里面的所有人
        $codes = Db::table($this->db.'.vip_vticket_rule')->field('staff_codes')->where('code', input('code'))->find();
        // 通过逗号拆分成字符串 拼接上自己
        $codes = implode(',', $codes).','.session('info.staff');
        // 显示该人员
        $data = Db::table($this->db.'.vip_staff')->field('code,name')->where('code', 'in', $codes)->select();
        webApi(0, '', 0, $data);
    }

    /**
     *  添加资金来源信息
     */
    public function addcapital()
    {   
        
        $data = [
            'vRule_name' => input('vRule_name'), // 规则code
            'capital_code' => input('capital_name'),    // 乐捐人code
            'capital_money' => input('capital_money'),  // 钱数
            'capital_reason' => input('capital_reason'),    // 乐捐理由
            'capital_time' => time()    // 添加时间
        ];

        // 验证器
        $result = $this->validate(input(),'app\web\validate\VcapitalValidate');
        if ($result!== true) {
            webApi(0, 'error', 0, $result);
        }

        //启动事物
        Db::startTrans();
        try {
            // 添加资金来源
            Db::table($this->db.'.vip_vcapital')->insert($data); 
            // 增加资金池  一条规则一个资金池
            Db::table($this->db.'.vip_vticket_rule')->where('code',input('vRule_name'))->setInc('price', input('capital_money'));
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $res = false; 
        }
            //提示信息
        if ($res) {
            webApi(0, 'yes', 0, '添加信息成功!');
        } else {
            webApi(0, 'no', 0, '添加信息失败!');
        }
    }
}