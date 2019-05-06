<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/05
 * Description 今日100天跟进营销
 */
class DayBack extends Common
{
    /**
     * 100天跟进
     */
    public function dayBackSel()
    {
        // 获取所需数据
        [$page, $limit, $search] = [input('page'), input('limit'), input('search')]; 
        //模糊查询
        if ($search !== '') {
            $where[] = ['name|vgname|vsname|vip_code|vyphone|vlname|vyname', 'like', '%' . $search . '%'];
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
        $count = Db::table($this->db.'.view_hundred_count')
                ->where($where)
                ->where($org)
                ->count();
        //查询的数据
        $data = Db::table($this->db.'.view_hundred_count')
                ->where($where)
                ->where($org)
                ->order('time','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();
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
        unset($page, $where, $limit, $operate, $org, $search);
        
        webApi(0, '', $count, $data);

    }

}