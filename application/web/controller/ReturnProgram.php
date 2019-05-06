<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/03
 * Description 返单计划营销
 */
class ReturnProgram extends Common
{
    /**
     * 总返单计划
     */
    public function programSel()
    {
        //分页信息
        [$page, $limit, $lookup] = [input('page'), input('limit'), input('search')];
        //模糊查询
        if ($lookup) {
            // $where[] = ['v.name|vz.name|vm.name|v.vip_code|vh.phone|vl.username|vh.username', 'like', '%' . $lookup . '%'];
            $where[] = ['name|vzname|vmname|vip_code|vhphone|vlusername|vhusername', 'like', '%' . $lookup . '%'];
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
        $count = Db::table($this->db.'.view_vip_plan')->where($where)->count();
        //查询的数据
        $data = Db::table($this->db.'.view_vip_plan')
                ->where($where)
                ->order('service_time','desc') //按照时间降序排列
                ->page($page, $limit)
                ->select();
        // $count = Db::table($this->db.'.vip_reorder')
        //         ->alias('v')
        //         ->leftJoin($this->db.'.vip_store vm', 'vm.code = v.store_code') //门店
        //         ->leftJoin($this->db.'.vip_org vz', 'vz.code = v.org_code') //所属机构
        //         ->leftJoin($this->db.'.vip_viplist vh', 'vh.code = v.vip_code') //会员列表
        //         ->leftJoin($this->db.'.vip_viplevel vl', 'vl.code = v.level_code')//会员级别
        //         ->leftJoin($this->db.'.vip_staff vg', 'vg.code = v.executor_code')//执行人
        //         ->leftJoin($this->db.'.vip_staff vgg', 'vgg.code = v.custodian_code')//监督人
        //         ->field('v.*,vm.name vmname,vz.name vzname, vh.username vhname, vh.phone vhphone, vl.username vlname,vg.name vgname, vgg.name vggname')
        //         ->where($where)
        //         ->where($org)
        //         ->count();

        // $data = Db::table($this->db.'.vip_reorder')
        //         ->alias('v')
        //         ->leftJoin($this->db.'.vip_store vm', 'vm.code = v.store_code') //门店
        //         ->leftJoin($this->db.'.vip_org vz', 'vz.code = v.org_code') //所属机构
        //         ->leftJoin($this->db.'.vip_viplist vh', 'vh.code = v.vip_code') //会员列表
        //         ->leftJoin($this->db.'.vip_viplevel vl', 'vl.code = v.level_code')//会员级别
        //         ->leftJoin($this->db.'.vip_staff vg', 'vg.code = v.executor_code')//执行人
        //         ->leftJoin($this->db.'.vip_staff vgg', 'vgg.code = v.custodian_code')//监督人
        //         ->field('v.*,vm.name vmname,vz.name vzname, vh.username vhname, vh.phone vhphone, vl.username vlname,vg.name vgname, vgg.name vggname')
        //         ->where($where)
        //         ->where($org)
        //         ->order('v.id','desc') //按照登记时间降序排列
        //         ->page($page, $limit)
        //         ->select();
        //更改格式
        // foreach ($data as $k => $v) {
        //     //日期格式
        //     $data[$k]['time_g'] = date('Y-m-d H:i:s', $v['service_time']);
        // }

        unset($page, $limit, $where, $lookup, $operate, $org);
        
        webApi(0, '', $count, $data);
    }

    /**
     * 修改到店
     */
    public function replaceGoShop()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // 获取状态
        $replace =Db::table($this->db.'.vip_reorder')->find($id);
        if (intval($replace['go_shop']) == 1) {
            webApi(0, 'error', 0, '已到店状态不能修改!');
        }

        $go_shop = intval(!$replace['go_shop']);

        $data = [
            'id' => $id, 'go_shop' => $go_shop
        ];
        // 执行修改
        $res = Db::table($this->db.'.vip_reorder')->update($data);

        unset($id, $go_shop, $data, $replace);

        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 修改消费
     */
    public function replaceConsumption()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // 获取状态
        $replace =Db::table($this->db.'.vip_reorder')->find($id);
        if (intval($replace['consumption']) == 1) {
            webApi(0, 'error', 0, '已消费状态不能修改!');
        }

        $consumption = intval(!$replace['consumption']);

        $data = [
            'id' => $id, 'consumption' => $consumption
        ];
        // 执行修改
        $res = Db::table($this->db.'.vip_reorder')->update($data);

        unset($id, $consumption, $data, $replace);

        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 修改状态
     */
    public function replace()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // 获取状态
        $replace =Db::table($this->db.'.vip_reorder')->find($id);
        if (intval($replace['status']) == 1) {
            webApi(0, 'error', 0, '已审核状态不能修改!');
        }

        $status = intval(!$replace['status']);

        $data = [
            'id' => $id, 'status' => $status
        ];
        // 执行修改
        $res = Db::table($this->db.'.vip_reorder')->update($data);

        unset($id, $status, $data, $replace);

        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 删除返单计划营销
     */
    public function programDel()
    {
        [$id, $bill] = [input('id'), input('bill')];
        if ($id == null || $bill == null) {
            webApi(1,'参数错误!');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_reorder')->delete($id);
            Db::table($this->db.'.vip_reorder_info')->where('reorder_code', $bill)->delete();
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        unset($id, $bill);

        if ($res) {
            webApi(0,'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 修改返单计划营销
     */
    public function programEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'name' => input('listplan_name'),
            'custodian_code' => input('custodian'),
            'executor_code' => input('executor'),
            'org_code' => '',
            'store_code' => '',
            'service_time' => strtotime(input('service_time')),
            'id' => $id
        ];
        $m = Db::table($this->db.'.vip_staff')
            ->where('code',$data['executor_code'])
            ->field('org_code, store_code')
            ->find();
        if ($m) {
            $data['org_code'] = $m['org_code'];
            $data['store_code'] = $m['store_code'];
        }
        $res = Db::table($this->db.'.vip_reorder')->update($data);
        unset($id, $data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 批量删除大返单计划
     */
    public function programDelAll()
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
            Db::table($this->db.'.vip_reorder')->where('id', 'in', $ids)->delete();
            Db::table($this->db.'.vip_reorder_info')->where('reorder_code', 'in', $bills)->delete();
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
     * 删除返单计划营销中的小返单
     */
    public function programDillDel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $res = Db::table($this->db.'.vip_reorder_info')->delete($id);
        unset($id);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 修改返单计划营销中的小返单
     */
    public function programDillEdit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $data = [
            'color' => input('color'),
            'size' => input('size'),
            'brand' => input('brand'),
            'photo' => input('photo'),
            'vip_price' => input('vip_price'),
            'goods_code' => input('production_id'),
            'goods_bar_code' => input('production_number'),
            'customer_demand' => input('customer_demand'),
            'buy_some' => input('buy_some'),
            'top' => input('top'),
            'question' => input('question'),
            'answer' => input('answer'),
            'id' => $id
        ];
        $res = Db::table($this->db.'.vip_reorder_info')->update($data);
        unset($id,$data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

}