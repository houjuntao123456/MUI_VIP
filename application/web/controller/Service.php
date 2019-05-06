<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
use app\web\controller\Common;

/**
 * Author lhp
 * Date 2018/12/28
 * Description 会员权益 --- 会员服务
 */
class Service extends Common
{
    /**
     * 会员服务列表
     */
    public function service()
    {   
         // 获取分页数据
        [$page, $limit, $lookup] = [input('page'), input('limit'), input('levellookup')];
        
         //统计数量
        $count = Db::table($this->db.'.vip_vipservice')->count();

        //模糊查询
        if ($lookup) {
            $where[] = ['vg.username|v.servicename', 'like', '%' . $lookup . '%'];
        } else {
            $where = true;
        }
        unset($lookup);
        //查询
        $data = Db::table($this->db.'.vip_vipservice')
            ->alias('v')
            ->leftJoin($this->db.'.vip_viplevel vg', 'vg.code = v.level_code')
            // ->leftJoin($this->db.'.vip_vipservice_company vp', 'vp.code = v.company') vp.username vpname,
            ->field('vg.username vgname, v.id, v.level_code, v.org_code, v.servicename, v.service_second, v.single_quantity, v.start_limited_time, v.end_limited_time, company')
            ->where($where)
            ->order('v.id', 'desc')
            ->page($page, $limit)
            ->select();
            unset($where, $page, $limit);
            foreach ($data as $k=>$v) {
                $data[$k]['start_limited_time'] = date('Y-m-d H:i:s', $v['start_limited_time']);
                $data[$k]['end_limited_time'] = date('Y-m-d H:i:s', $v['end_limited_time']);
                $data[$k]['limited_time'] = $data[$k]['start_limited_time']. '—' . $data[$k]['end_limited_time'];
            }
        webApi(0, '', $count, $data);
    }

    /**
     * 会员服务 添加功能
     */
    public function serviceadd()
    {
        $up = [
            'level_code' => input('level_code'),
            'servicename' => input('servicename'),
            'service_second' => input('service_second'),
            'start_limited_time' => strtotime(input('start_limited_time')),
            'end_limited_time' => strtotime(input('end_limited_time')),
            'single_quantity' => input('single_quantity'),
            'company' => input('company'),
            'code' => 'SERVICE'.str_replace('.' , '', microtime(1))
        ];
        $org = explode(',', input('org_code'));
        $inserData = [];
        for ($i = 0; $i < count($org); $i++) {
            $inserData[$i] = $up;
            $inserData[$i]['org_code'] = $org[$i];
        }

        //验证场景
        // $result = $this->validate($up, 'app\web\validate\v1\ServiceValidate.serviceadd');
        // if (true !== $result) {
        //     webApi(0, 'error', 0, $result);
        // }
        // unset($result);
        $data = Db::table($this->db.'.vip_vipservice')->insertAll($inserData);
        unset($up);
        if ($data) {
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 工具条删除功能
     */
    public function servicedel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $res = Db::table($this->db.'.vip_vipservice')->delete($id);
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
    public function serviceedit()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // $this->actionLock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        $data = [
            'id' => $id,
            'level_code' => input('level_code'),//会员登记
            'servicename' => input('servicename'),//服务名称
            'service_second' => input('service_second'), //服务次数
            'start_limited_time' => strtotime(input('start_limited_time')),
            'end_limited_time' => strtotime(input('end_limited_time')),
            'single_quantity' => input('single_quantity'),
            'company' => input('company'),
            'org_code' => input('org_code')
        ];
        //验证场景
        // $result = $this->validate($data, 'app\web\validate\v1\ServiceValidate.serviceadd');
        // if (true !== $result) {
        //     $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //     webApi(0, 'error', 0, $result);
        // }
        // unset($result);
        //修改数据库信息
        $res = Db::table($this->db.'.vip_vipservice')->update($data);
        unset($data);
        // $this->actionUnlock(input('access_token'), request()->controller().'_'.request()->action(), $id);
        //提示信息
        if($res){
            webApi(0, 'yes', 0, '修改信息成功!');
        }else if ($res === false){
            webApi(0, 'no', 0, '修改信息失败!');
        } else {
            webApi(0, 'no', 0, '未修改!');
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
        $res = Db::table($this->db.'.vip_vipservice')->where('id', 'in', $ids)->delete();
        unset($ids);
        if ($res) {
            webApi(0, 'yes', 0, '删除成功');
        } else {
            webApi(0, 'no', 0, '删除失败');
        }
    }

}