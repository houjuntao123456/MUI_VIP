<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/21
 * Description 返单计划报表
 */
class PlanDat extends Common
{
    /**
     * 查询报表
     */
    public function index()
    {
        //分页
        [$limit, $page, $db] = [input('limit'), input('page'), $this->db];

        $getWhere = $this->datLeftTopWhere(input('search')); // 调用公共方法

        if (empty(session('info.store'))) {
            $storeWhere = true;
        } else {
            $storeWhere[] = ['o.store_id', '=', session('info.store')['ERP']];
        }
        
        $data = Db::table($db.'.view_plan_count')
                ->alias('o') // 只要是调用公共封装方法 必须叫 o
                ->leftJoin($db.'.view_left_top t', 't.ERP = o.store_id') // 所有报表 必须关联
                ->field('o.*,t.store_name,t.org_name') // t.store_name t.org_name 必须获取
                ->where($getWhere['where']) // 这个必须要
                ->where($storeWhere)
                ->page($page, $limit)
                ->select();
        
        // 这个if  全部都要
        if ($data) {
            foreach ($data as $k=>$v) {
                // $data[$k]['org'] = $this->recursiveSearch($v, $getWhere['storeyClass']);
                 switch ($getWhere['storeyClass']) {
                    case 'top':
                        $data[$k]['org'] = $v['org_name'];
                    break;
                    case 'third':
                        $data[$k]['org'] = $v['store_name'];
                    break;
                    case 'fourth':
                        $data[$k]['org'] = $v['staff_name'];
                    break;
                    case 'fifth':
                        $data[$k]['org'] = $v['staff_name'];
                    break;
                }
            }
        }

        // 这个->where($getWhere['where'])必须要
        $count = Db::table($db.'.view_plan_count o')->where($getWhere['where'])->where($storeWhere)->count();
        webApi(0, '', $count, $data);
    }

    /**
     * 查询组织机构来限制门店
     */
    public function planstore()
    {
        $data = Db::table($this->db.'.vip_org')->where('code', input('org'))->find();
        if (empty(Db::table($this->db.'.vip_org')->where('pid', $data['code'])->find())) {
            $data['under'] = 1;
        } else {
            $data['under'] = 0;
        }
        //查询门店
        if ($data['under'] == 1) {
            $store = Db::table($this->db.'.vip_store')->where('org_code', $data['code'])->select();
        } else {
            $store = [] ;
        }
        webApi(0, '', 0, $store);
    }

    /**
     * 用门店的code查询员工
     */
    public function planstaff()
    {
        $data = Db::table($this->db.'.vip_staff')->where('store_code', input('store'))->select();
        webApi(0, '', 0, $data);
    }
}