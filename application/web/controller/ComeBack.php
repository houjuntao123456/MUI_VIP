<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/01
 * Description 100跟进营销
 */
class ComeBack extends Common
{
    /**
     * 100跟进
     */
    public function backSel()
    {
        //分页信息
        [$page, $limit] = [input('page'), input('limit')];
        
        //模糊查询
        if (input('search') !== '') {
            $where[] = ['name|vgname|vsname|vip_code|vyphone|vlname|vyname', 'like', '%' . input('search') . '%'];
        } else {
            $where = true;
        }
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();
        //所属机构限制
        if ($operate['id'] == 1) {
            $org = true;
        } else {//判断是否是管理
            if ($operate['role'] == 0) {//管理查询管理机构与自己的所属机构
                $org[] = ['org_code', 'in', $operate['admin_org_code'].','.$operate['org_code']];
            } else { //员工查询所属机构
                $org[] = ['org_code', '=', $operate['org_code']];
            }
        }
        //统计数量
        $count = Db::table($this->db.'.view_vip_hundred')
                ->where($where)
                ->where($org)
                ->count();
        //查询的数据
        $data = Db::table($this->db.'.view_vip_hundred')
                ->where($where)
                ->where($org)
                ->order('time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();
        // $count = Db::table($this->db.'.vip_hundred_interaction_foot')
        //         ->alias('v')
        //         ->leftJoin($this->db.'.vip_viplist vy', 'vy.code = v.vip_code') //会员列表
        //         ->leftJoin($this->db.'.vip_viplevel vl', 'vl.code = v.level_code')//会员级别
        //         ->leftJoin($this->db. '.vip_staff vh', 'vh.code = v.executor_code')//执行人
        //         ->leftJoin($this->db. '.vip_staff vhh', 'vhh.code = v.custodian_code')//监督人
        //         ->leftJoin($this->db. '.vip_org vg', 'vg.code = vh.org_code')//所属机构
        //         ->leftJoin($this->db. '.vip_store vs', 'vs.code = vh.store_code')//门店
        //         ->field('v.*, vh.name vhname, vhh.name vhhname, vg.name vgname, vs.name vsname, vy.username vyname, vy.phone vyphone, vl.username vlname')
        //         ->where($where)
        //         ->where($org)
        //         ->count();
        // // 查询数据
        // $data = Db::table($this->db.'.vip_hundred_interaction_foot')
        //         ->alias('v')
        //         ->leftJoin($this->db.'.vip_viplist vy', 'vy.code = v.vip_code') //会员列表
        //         ->leftJoin($this->db.'.vip_viplevel vl', 'vl.code = v.level_code')//会员级别
        //         ->leftJoin($this->db. '.vip_staff vh', 'vh.code = v.executor_code')//执行人
        //         ->leftJoin($this->db. '.vip_staff vhh', 'vhh.code = v.custodian_code')//监督人
        //         ->leftJoin($this->db. '.vip_org vg', 'vg.code = vh.org_code')//所属机构
        //         ->leftJoin($this->db. '.vip_store vs', 'vs.code = vh.store_code')//门店
        //         ->field('v.*, vh.name vhname, vhh.name vhhname, vg.name vgname, vs.name vsname, vy.username vyname, vy.phone vyphone, vl.username vlname')
        //         ->where($where)
        //         ->where($org)
        //         ->order('v.time','desc') //按照登记时间降序排列
        //         ->page($page, $limit)
        //         ->select();
        
        //更改格式
        // foreach ($data as $k => $v) {
        //     if ($v['time'] !== 0) {
        //         $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['time']);
        //     }
        // }
        // 修改格式
        foreach ($data as $k => $v) { 
            //查询总数
            $data[$k]['count'] = Db::table($this->db.'.vip_hundred_interaction_foot_info')->where('hundred_foot_code', $v['code'])->count();
            //查询审核数
            $data[$k]['yes'] = Db::table($this->db.'.vip_hundred_interaction_foot_info')->where('hundred_foot_code', $v['code'])->where('status', 1)->count();
        }
        //完成率
        foreach ($data as $k=>$v) {
            $data[$k]['suc'] = round(($v['yes'] / $v['count']) * 100);
        }
        //拼接百分比
        foreach ($data as $k=>$v) {
            $data[$k]['suc'] = $v['suc'].'%';
        }
        unset($page, $where, $limit, $operate, $org);
        
        webApi(0, '', $count, $data);
    }

    /**
     * 修改状态
     */
    public function backStatus()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        // 获取状态
        $replace =Db::table($this->db.'.vip_hundred_interaction_foot')->find($id);
        if ($replace['status'] == 1) {
            webApi(0, 'error', 0, '已互动状态不能修改!');
        }
        //记录状态获取
        $record = Db::table($this->db.'.vip_interaction_record')->where('code', $replace['code'])->find();
        
        //修改状态
        $status = intval(!$replace['status']);

        $data = [
            'id' => $id, 'status' => $status
        ];
        //修改100跟进记录中的状态
        $statued = intval(!$record['status']);

        $uprecord = [
            'id' => $record['id'],
            'status' => $statued
        ];

        // 启动事务
        Db::startTrans();
        try {
            // 执行修改
            Db::table($this->db.'.vip_hundred_interaction_foot')->update($data);
            Db::table($this->db.'.vip_interaction_record')->update($uprecord);
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        unset($id,$status,$data,$replace);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 修改小100天跟进审核状态
     */
    public function backDillStatus()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        // 获取状态
        $replace = Db::table($this->db.'.vip_hundred_interaction_foot_info')->find($id);
        if (intval($replace['status']) == 1) {
            webApi(0, 'error', 0, '已审核状态不能修改!');
        }
        
        //修改状态
        $status = intval(!$replace['status']);
        
        $data = [
            'id' => $id, 'status' => $status
        ];

        // 执行修改
        $res =  Db::table($this->db.'.vip_hundred_interaction_foot_info')->update($data);
        unset($id,$status,$data,$replace);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 删除100跟进营销
     */
    public function backDel()
    {
        [$id, $bill] = [input('id'), input('bill')];
        if ($id == null || $bill == null) {
            webApi(1,'参数错误');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_hundred_interaction_foot')->delete($id);
            Db::table($this->db.'.vip_hundred_interaction_foot_info')->where('hundred_foot_code', $bill)->delete();
            Db::table($this->db.'.vip_interaction_record')->where('code', $bill)->delete();
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }
        unset($id,$bill);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 修改100跟进营销
     */
    public function backEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $staff = Db::table($this->db.'.vip_staff')->where('code', input('executor'))->find();

        $data = [
            'custodian_code' => input('custodian'),
            'executor_code' => input('executor'),
            'org_code' => $staff['org_code'],
            'store_code' => $staff['store_code'],
            'id' => $id
        ];

        $res = Db::table($this->db.'.vip_hundred_interaction_foot')->update($data);
        unset($id,$data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 批量删除100跟进营销
     */
    public function backDelAll()
    {
        [$ids, $bills] = [input('ids'), input('bill')];
        if ($ids == null || $bills == null) {
            webApi(1,'参数错误');
        }

        $ids = trim($ids, ',');
        $bills = trim($bills, ',');
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_hundred_interaction_foot')->where('id', 'in', $ids)->delete();
            Db::table($this->db.'.vip_hundred_interaction_foot_info')->where('hundred_foot_code', 'in', $bills)->delete();
            Db::table($this->db.'.vip_interaction_record')->where('code', 'in', $bills)->delete();
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }
        unset($ids,$bills);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 删除100跟进营销中的小100天跟进
     */
    public function backDillDel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $res = Db::table($this->db.'.vip_hundred_interaction_foot_info')->delete($id);
        unset($id);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 修改100跟进营销中的小100天跟进
     */
    public function backDillEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'time' => input('time'),
            'purpose' => input('purpose'),
            'function' => input('function'),
            'speech' => input('speech'),
            'id' => $id
        ];

        $res = Db::table($this->db.'.vip_hundred_interaction_foot_info')->update($data);
        unset($id,$data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }
}