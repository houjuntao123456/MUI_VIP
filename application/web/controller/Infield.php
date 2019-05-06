<?php

namespace app\web\controller;

use think\Db;
use think\Controller;
// use app\web\controller\Common;

/**
 * Author lxy
 * Date 2019/01/26
 * Description 专场跟进
 */
class Infield extends Common
{
    /**
     * 查询专场跟进
     */
    public function fieldSel()
    {
        //分页信息
        [$page, $limit] = [input('page'), input('limit')];
        //模糊查询
        if (input('search') != '') {
            $where[] = ['v.name|vg.name', 'like', '%' . input('search') . '%'];
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
                $org[] = ['v.org_code', 'in', $operate['admin_org_code'].','.$operate['org_code']];
            } else { //员工查询所属机构
                $org[] = ['v.org_code', '=', $operate['org_code']];
            }
        }
        //统计数量
        $count = Db::table($this->db.'.vip_field_interaction_tpl')
                ->alias('v')
                ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($where)
                ->where($org)
                ->count();
        // 所需数据
        $data = Db::table($this->db.'.vip_field_interaction_tpl')
                ->alias('v')
                ->leftJoin($this->db.'.vip_org vg', 'vg.code = v.org_code')
                ->field('v.*,vg.name vgname')
                ->where($where)
                ->where($org)
                ->order('v.id','desc') //按照登记时间降序排列
                ->page($page, $limit)
                ->select();

        unset($page, $limit, $where, $org);

        webApi(0, '', $count, $data);
    }

    /**
     * 专场跟进按单号查询
     */
    public function fieldDillSel()
    {
        $bill = input('bill') ?? null;
        if ($bill == null) {
            webApi(1, '参数错误');
        }
        // 统计数量
        $count = Db::table($this->db.'.vip_field_interaction_tpl_info')->where('field_tpl_code',$bill)->count();
        // 所需数据
        $data = Db::table($this->db.'.vip_field_interaction_tpl_info')->where('field_tpl_code',$bill)->select();
        //更改格式
        foreach ($data as $k => $v) {
            $data[$k]['time_g'] = date('Y-m-d', $v['time']);
        }

        unset($bill);

        webApi(0, '', $count, $data);
    }

    /**
     * 专场跟进添加
     */
    public function fieldAdd()
    {
        $bill = 'MBHTHD'.str_replace('.' , '', microtime(1));
        $token = input('access_token');
        $datacache = cache('inf_'.$token);
        if ($datacache == null) {
            webApi(0, 'error',0,'添加失败,请创建专场跟进!');
        }

        $data = [
            'code' => $bill,
            'org_code' => input('splb'),
            'name' => input('name')
        ];

        $z = Db::table($this->db.'.vip_org')->where('pid',$data['org_code'])->find();

        if ($z) {
            webApi(0, 'error', 0, '所属机构必须选最底层！');
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_field_interaction_tpl')->insert($data);
            foreach($datacache as $k=>$v){
                $c = array_merge($v , array('field_tpl_code' => $bill));
                Db::table($this->db.'.vip_field_interaction_tpl_info')->insert($c);
            }
            // 提交事务
            Db::commit();
            $res = true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e);
            $res = false;
        }

        unset($bill, $data, $c);

        if ($res) {
            cache('inf_' . $token, null);
            webApi(0,'yes', 0, '添加成功!');
        } else {
            webApi(0,'no', 0, '添加失败!');
        }
    }

    /**
     * 查出缓存中的小专场内容
     */
    public function fieldCacheSel()
    {   
        $token = input('access_token');
        $datacache = cache('inf_'.$token);
        if ($datacache) {
            foreach ($datacache as $k => $v) {
                $datacache[$k]['time_g'] = date('Y-m-d', $v['time']);
            }
        }
        webApi(0, '', 0, $datacache);
    }

    /**
     * 小专场添加到緩存
     */
    public function fieldCacheAdd()
    {
        $token = input('access_token');
        $d = [
            'delid' => 'mhthd'.str_replace('.' , '', microtime(1)),
            'time'=> strtotime(input('time')),
            'purpose'=> input('purpose'),
            'function'=> input('function'),
            'speech'=> input('speech')
        ];
        if (cache('?inf_'.$token)) {
            $data = cache('inf_'.$token);
        } else {
            $data = [];
        }
        array_push($data, $d);
        cache('inf_'.$token, $data, 3600);
        unset($d);
        if(cache('inf_'.$token) !== ""){
            webApi(0, 'yes', 0, '添加成功!');
        }else {
            webApi(0,'no', 0, '添加失败!');
        }
    }

    /**
     * 清除缓存
     */
    public function fieldCacheClean()
    {
        $token = input('access_token');
        cache('inf_'.$token, null);
        webApi(0, '清除缓存！');
    }

    /**
     * 小专场删除
     */
    public function fieldCacheDel()
    {
        $id = input('id') ?? null;
        if ($id == null) {
            webApi(1, '参数错误');
        }
        $token = input('access_token');
        $datacache = cache('inf_'.$token);

        if (count($datacache) > 1) {
            foreach ($datacache as $k=>$v) {
                if ($id == $v['delid']) {
                    unset($datacache[$k]);
                }
            }
            sort($datacache);
            cache('inf_'.$token, $datacache, 3600);
        } else {
            cache('inf_'.$token, null);
        }
        unset($id);
        webApi(0,'删除成功!');
    }

    /**
     * 删除专场跟进也删除中的小专场跟进
     */
    public function fieldDel()
    {
        [$id, $bill] = [input('id'), input('bill')];
        if ($id == null || $bill == null) {
            webApi(1,'参数错误');
        }
        //启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_field_interaction_tpl')->delete($id);
            Db::table($this->db.'.vip_field_interaction_tpl_info')->where('field_tpl_code', $bill)->delete();
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
     * 批量删除专场跟进也删除中的小专场跟进
     */
    public function fieldDelAll()
    {
        [$ids, $bills] = [input('ids'), input('bill')];
        if ($ids == null || $bills == null) {
            webApi(1,'参数错误');
        }

        $ids = trim($ids, ',');
        $bills = trim($bills, ',');
        //启动事务
        Db::startTrans();
        try {
            Db::table($this->db.'.vip_field_interaction_tpl')->where('id', 'in', $ids)->delete();
            Db::table($this->db.'.vip_field_interaction_tpl_info')->where('field_tpl_code', 'in', $bills)->delete();
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
     * 编辑互动名称与组织机构
     */
    public function fieldEdit()
    {
        $id = intval(input('id')) ?? null;
        if ($id == null) {
            webApi(1, '参数错误!');
        }

        $data = [
            'id' => $id,
            'org_code' => input('splb'),
            'name' => input('name')
        ];
        $res = Db::table($this->db.'.vip_field_interaction_tpl')->update($data);
        
        unset($data, $id);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

    /**
     * 修改小专场跟进 
     */
    public function fieldEditSmall()
    {
        $id = intval(input('id'))?? null;
        if ($id == null) {
            webApi(1, '参数错误!');
        }
        $data = [
            'id' => $id,
            'time' => strtotime(input('time')),
            'purpose' => input('purpose'),
            'function' => input('function'),
            'speech' => input('speech')
        ];
        
        $res = Db::table($this->db.'.vip_field_interaction_tpl_info')->update($data);
        
        unset($data, $id);
        if ($res) {
            webApi(0, 'yes', 0, '修改成功!');
        } else {
            webApi(0, 'no', 0, '修改失败!');
        }
    }

}