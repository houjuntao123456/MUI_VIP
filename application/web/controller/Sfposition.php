<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;
use app\web\service\ErpWhere as EW;

/**
 * Author lxy
 * Date 2018/12/28
 * Description 职位管理
 */
class Sfposition extends Common
{
    /**
     * 职位查表
     */
    public function positionSel()
    {
        //获取分页数据
        [$page,$limit] = [input('page'),input('limit')];
        //查询当前登入人
        $operate = Db::table($this->db.'.vip_staff')->where('code', session('info.staff'))->find();
        //所属机构限制
        if ($operate['id'] == 1) {
            $where = true;
        } else {//判断是否是管理
            if ($operate['role'] == 0) {//管理查询管理机构
                $where[] = ['v.org_code', 'in', $operate['admin_org_code']];
            } else { //员工查询所属机构
                $where[] = ['v.org_code', '=', $operate['org_code']];
            }
        }
        //统计数量
        $count = Db::table($this->db.'.vip_position')
                ->alias('v')
                ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($where)
                ->count();
        //查询
        $data = Db::table($this->db.'.vip_position')
                ->alias('v')
                ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($where)
                ->order('v.sort','asc')
                ->page($page, $limit)
                ->select();

        unset($page, $limit);

        foreach ($data as $k => $v) {
            //时间格式的转换
            $data[$k]['create_time_g'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        webApi(0, '', $count, $data);
    }

    /**
     * 添加职位
     */
    public function addPosition()
    {
        $data = [
            'sort' => input('sort'),
            'name' => input('name'),
            'bewrite' => input('bewrite'),
            'org_code' => input('splb'),
            'create_time' => time(),
            'code' => 'YGZW'.str_replace('.' , '', microtime(1))
        ];

        $this->Sectionjudge($data);
        $z = Db::table($this->db.'.vip_org')->where('pid',$data['org_code'])->find();

        if ($z) {
            webApi(0, 'error', 0, '所属机构必须选最底层！');
        }

        $res = Db::table($this->db.'.vip_position')->insert($data);

        unset($data);

        if ($res) {
            webApi(0, 'yes', 0, '添加成功!');
        } else {
            webApi(0, 'no', 0, '添加失败!');
        }
    }

    /**
     * 删除职位
     */
    public function delPosition()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        // 执行删除
        $res = Db::table($this->db.'.vip_position')->delete($id);

        unset($id);

        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 批量删除
     */
    public function delPositionAll()
    {
        if (input('ids') == '') {
            webApi(1, '参数错误');
        }
        
        $ids = trim(input('ids'), ',');
        $res = Db::table($this->db.'.vip_position')->where('id', 'in', $ids)->delete();

        unset($ids);

        if ($res) {
            webApi(0, 'yes', 0, '删除成功!');
        } else {
            webApi(0, 'no', 0, '删除失败!');
        }
    }

    /**
     * 修改职位
     */
    public function editPosition()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }

        $data = [
            'sort' => input('sort'),
            'name' => input('name'), 
            'bewrite' => input('bewrite'), 
            'org_code' => input('splb'),
            'id' => $id
        ];

        $this->Sectionjudge($data, $id);
        $res = Db::table($this->db.'.vip_position')->update($data);

        $d = Db::table($this->db.'.vip_org')->where('name', $data['org_code'])->field('code')->find();

        if ($d['code'] == "") {
            $data['org_code'];
        } else {
            $data['org_code']= $d['code'];
        }
        
        $z = Db::table($this->db.'.vip_org')->where('pid',$data['org_code'])->find();

        if ($z) {
            webApi(0, 'error', 0, '所属机构必须选最底层！');
        }

        unset($id, $data);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 限制
     */
    protected function Sectionjudge($up, $id = 0)
    {
        if ($id !== 0) {
            $where[] = ['id', '<>', $id];
        } else {
            $where = true;
        }

        $number = Db::table($this->db.'.vip_position')
                ->where($where)
                ->where('sort', $up['sort'])
                ->find();

        if ($number) {
            webApi(0, 'error', 0, '序号不能重复！');
        }

        $name = Db::table($this->db.'.vip_position')
                ->where($where)
                ->where('org_code', $up['org_code'])
                ->where('name', $up['name'])
                ->find();
                
        if ($name) {
            webApi(0, 'error', 0, '职位名称不能重复！');
        }
    }

}