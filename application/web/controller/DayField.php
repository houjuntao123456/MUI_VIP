<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/02/14
 * Description 今日专场跟进营销
 */
class DayField extends Common
{
    /**
     * 专场跟进
     */
    public function dayFieldSel()
    {
        //获取所需数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')];
        
        //模糊查询
        if ($search !== '') {
            $where[] = ['name|vgname|vsname|vip_code|vyname|vyphone|vlname', 'like', '%' . $search . '%'];
        } else {
            $where = true;
        }
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();
        //所属机构限制
        if ($operate['id'] == 1) {
            $org = true;
        } else {//判断是否是管理
            if ($operate['role'] == 0) {//管理查询管理机构
                $org[] = ['org_code', 'in', $operate['admin_org_code'].','.$operate['org_code']];
            } else { //员工查询所属机构
                $org[] = ['org_code', '=', $operate['org_code']];
            }
        }
        //统计数量
        $count = Db::table($this->db.'.view_vip_field')
                ->whereTime('vsmtime', 'today')
                ->where($where)
                ->where($org)
                ->count();
        //查询的数据
        $data = Db::table($this->db.'.view_vip_field')
                ->whereTime('vsmtime', 'today')
                ->where($where)
                ->where($org)
                ->order('time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();
        
        unset($page, $where, $limit, $operate, $org, $search);
        // 修改格式
        foreach ($data as $k => $v) { 
            //查询总数
            $data[$k]['count'] = Db::table($this->db.'.vip_field_interaction_foot_info')->where('field_foot_code', $v['code'])->count();
            //查询审核数
            $data[$k]['yes'] = Db::table($this->db.'.vip_field_interaction_foot_info')->where('field_foot_code', $v['code'])->where('status', 1)->count();
        }
        //完成率
        foreach ($data as $k=>$v) {
            $data[$k]['suc'] = round(($v['yes'] / $v['count']) * 100);
        }
        //拼接百分比
        foreach ($data as $k=>$v) {
            $data[$k]['suc'] = $v['suc'].'%';
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 专场跟进按单号查询小专场跟进
     */
    public function dayFieldDill()
    {
        $bill = input('bill') ?? null;
        if ($bill == null) {
            webApi(1, '参数错误');
        }
        //统计数量
        $count = Db::table($this->db.'.vip_field_interaction_foot_info')
                ->where('field_foot_code',$bill)
                ->whereTime('time', 'today')
                ->count();
        //所需数据
        $data = Db::table($this->db.'.vip_field_interaction_foot_info')
                ->where('field_foot_code',$bill)
                ->whereTime('time', 'today')
                ->select();  
        
        foreach ($data as $k => $v) {
            $data[$k]['time_g'] = date('Y-m-d', $v['time']);
        }

        unset($bill);
        webApi(0, '', $count, $data);
    }
}